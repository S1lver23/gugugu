const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const fetch = require('node-fetch');
const mailer = require('./mailer');
const hashToken = value => crypto.createHash('sha256').update(value).digest('hex');
const parseConfig = row => typeof row.config === 'string' ? JSON.parse(row.config) : row.config;
const parseContent = row => typeof row.content === 'string' ? JSON.parse(row.content) : row.content;
const positive = value => value === null || value === '' || value === undefined ? null : (Number.isFinite(Number(value)) && Number(value) > 0 ? Number(value) : null);

function normalizeRule(input) {
    const symbol = String(input.symbol || '').trim().toUpperCase().replace(/\.TW[O]?$/, '');
    if (!/^[0-9A-Z]{4,8}$/.test(symbol) || !['tse','otc'].includes(input.market)) throw new Error('請確認股票代號與上市／上櫃市場');
    const uid = String(input.uid || '');
    if (!/^[A-Za-z0-9_-]{1,64}$/.test(uid)) throw new Error('提醒識別碼無效');
    const stopPrice = positive(input.stopPrice), takePrice = positive(input.takePrice);
    const deadline = input.deadline ? new Date(input.deadline) : null;
    if (deadline && !Number.isFinite(deadline.getTime())) throw new Error('提醒期限無效');
    if (!stopPrice && !takePrice && !deadline) throw new Error('至少設定停損、獲利或提醒期限其中一項');
    if (stopPrice && takePrice && stopPrice >= takePrice) throw new Error('停損價必須低於獲利價');
    const buyPrice = positive(input.buyPrice);
    if (buyPrice && ((stopPrice && stopPrice >= buyPrice) || (takePrice && takePrice <= buyPrice))) throw new Error('停損／獲利價與買進價不符');
    return {
        uid, symbol, market: input.market, name: String(input.name || '').slice(0, 80),
        buyPrice, stopPrice, takePrice, currentPrice: null,
        stopPercent: positive(input.stopPercent) || 5, takePercent: positive(input.takePercent) || 10,
        suggestedStopPrice: positive(input.suggestedStopPrice), suggestedTakePrice: positive(input.suggestedTakePrice),
        stopPoints: positive(input.stopPoints), takePoints: positive(input.takePoints),
        deadline: deadline ? deadline.toISOString() : '', emailEnabled: input.emailEnabled === true
    };
}

function readLiveQuote(item, now = Date.now()) {
    if (!item || !positive(item.z)) return null;
    const date = String(item.d || '');
    const time = String(item.t || '');
    if (!/^\d{8}$/.test(date) || !/^\d{2}:\d{2}:\d{2}$/.test(time)) return null;
    const isoDate = `${date.slice(0,4)}-${date.slice(4,6)}-${date.slice(6,8)}`;
    const sourceTime = Date.parse(`${isoDate}T${time}+08:00`);
    const today = new Intl.DateTimeFormat('en-CA', { timeZone:'Asia/Taipei', year:'numeric', month:'2-digit', day:'2-digit' }).format(now);
    if (isoDate !== today || !Number.isFinite(sourceTime) || sourceTime > now + 60000 || now - sourceTime > 300000) return null;
    return { price: Number(item.z), time: `${isoDate} ${time}`, name: String(item.n || '') };
}

function triggeredTypes(rule, quote, now = Date.now()) {
    const result = [];
    if (quote && positive(quote.price)) {
        if (rule.stopPrice && quote.price <= rule.stopPrice) result.push('stop');
        if (rule.takePrice && quote.price >= rule.takePrice) result.push('profit');
    }
    if (rule.deadline && now >= Date.parse(rule.deadline)) result.push('time');
    return result;
}

function createReminders({ pool, requireLogin, requireCsrf, writeRateLimiter, emailRateLimiter }) {
    const router = require('express').Router();
    let running = false;
    let latestQuotes = new Map();
    async function initialize() {
        const sql = fs.readFileSync(path.join(__dirname,'../database/reminders.sql'), 'utf8');
        for (const statement of sql.split(';').map(s=>s.trim()).filter(Boolean)) await pool.query(statement);
    }
    async function getRules(userId) {
        const [rows] = await pool.execute('SELECT config FROM alert_rules WHERE user_id = ? ORDER BY id', [userId]);
        return rows.map(parseConfig);
    }
    router.get('/email-settings', requireLogin, async (req,res) => {
        const [rows] = await pool.execute('SELECT email,verified_at,last_sent_at FROM notification_profiles WHERE user_id = ?', [req.session.user.id]);
        res.json({success:true,email:rows[0]?.email || '',verified:Boolean(rows[0]?.verified_at),lastSentAt:Number(rows[0]?.last_sent_at || 0),emailAvailable:mailer.configured() && process.env.EMAIL_ENABLED==='true'});
    });
    router.post('/email-settings', requireLogin, requireCsrf, emailRateLimiter, async (req,res) => {
        if (!mailer.configured() || process.env.EMAIL_ENABLED!=='true') return res.status(503).json({success:false,message:'寄信服務尚未啟用'});
        const email = String(req.body.email || '').trim().toLowerCase();
        if (email.length>254 || !/^[A-Za-z0-9.!#$%&'*+/=?^_`{|}~-]+@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)+$/.test(email)) return res.status(400).json({success:false,message:'請輸入有效的通知信箱'});
        const appUrl = new URL(process.env.APP_URL || 'http://localhost/gugugu/');
        if (appUrl.protocol !== 'https:' && !(appUrl.protocol === 'http:' && ['localhost','127.0.0.1'].includes(appUrl.hostname))) return res.status(503).json({success:false,message:'正式網站驗證連結必須使用 HTTPS'});
        const conn = await pool.getConnection();
        try {
            await conn.beginTransaction();
            await conn.execute('INSERT IGNORE INTO notification_profiles (user_id,email) VALUES (?,?)', [req.session.user.id,email]);
            const [rows] = await conn.execute('SELECT last_sent_at,email FROM notification_profiles WHERE user_id = ? FOR UPDATE', [req.session.user.id]);
            if (Date.now()-Number(rows[0].last_sent_at)<60000) { await conn.rollback();return res.status(429).json({success:false,message:'請等待一分鐘後再寄驗證信'}); }
            const token=crypto.randomBytes(32).toString('hex');
            await conn.execute('DELETE FROM email_verifications WHERE user_id = ?', [req.session.user.id]);
            await conn.execute('INSERT INTO email_verifications (token_hash,user_id,email,expires_at) VALUES (?,?,?,?)', [hashToken(token),req.session.user.id,email,Date.now()+30*60000]);
            const link=new URL('verify-email.php',appUrl.href.replace(/\/?$/,'/'));link.hash=`token=${token}`;
            await mailer.sendMail({to:email,subject:'古估股｜驗證通知信箱',text:`你已申請將此信箱綁定為古估股的通知信箱。\n請在 30 分鐘內開啟以下連結並按下「確認驗證」：\n${link.href}\n若不是你提出的申請，請忽略此信。`});
            await conn.execute('UPDATE notification_profiles SET verified_at=IF(email=?,verified_at,NULL),email=?,last_sent_at=? WHERE user_id=?', [email,email,Date.now(),req.session.user.id]);
            // 信箱變更後，尚未寄出的舊收件信件撤銷。
            await conn.execute("UPDATE alert_events SET email_state='cancelled' WHERE user_id=? AND email<>? AND email_state IN ('pending','sending')", [req.session.user.id,email]);
            await conn.commit();res.json({success:true,message:'驗證信已交給寄信服務，請到信箱開啟連結（也請查看垃圾郵件）。'});
        } catch (error) {
            await conn.rollback();console.error('[驗證信] 寄送失敗：',error.code || 'SMTP／資料庫錯誤');
            res.status(503).json({success:false,message:'驗證信寄送失敗，請稍後重試；原設定尚未變更。'});
        } finally {conn.release();}
    });
    router.post('/verify-email', emailRateLimiter, async(req,res)=>{
        const token=String(req.body.token || '');
        if(!/^[a-f0-9]{64}$/.test(token))return res.status(400).json({success:false,message:'驗證連結無效'});
        const conn=await pool.getConnection();
        try {
            await conn.beginTransaction();
            const [rows]=await conn.execute('SELECT * FROM email_verifications WHERE token_hash=? AND expires_at>? FOR UPDATE',[hashToken(token),Date.now()]);
            if(!rows.length)throw new Error('驗證連結已失效，請重新寄送');
            const [updated]=await conn.execute('UPDATE notification_profiles SET verified_at=UTC_TIMESTAMP() WHERE user_id=? AND email=?',[rows[0].user_id,rows[0].email]);
            if(!updated.affectedRows)throw new Error('信箱已變更，請重新寄送驗證信');
            await conn.execute('DELETE FROM email_verifications WHERE user_id=?',[rows[0].user_id]);
            await conn.commit();res.json({success:true,message:'通知信箱驗證完成！請回首頁勾選 Email 提醒並儲存規則。'});
        } catch(error){await conn.rollback();res.status(400).json({success:false,message:error.message});}finally{conn.release();}
    });
    router.get('/risk-rules',requireLogin,async(req,res)=>res.json({success:true,rules:await getRules(req.session.user.id)}));
    router.get('/risk-prices',requireLogin,async(req,res)=>{
        const rules=await getRules(req.session.user.id);
        const quotes=rules.map(rule=>{
            const quote=latestQuotes.get(`${rule.market}:${rule.symbol}`);
            const fresh=quote && Date.now()-Date.parse(quote.time.replace(' ','T')+'+08:00')<=300000;
            return {id:rule.symbol,name:quote?.name || rule.name,price:fresh?quote.price:0,time:fresh?quote.time:0,available:Boolean(fresh),reason:fresh?'':'尚無近期有效 MIS 報價，數值以 0 顯示。'};
        });
        res.json({success:true,quotes});
    });
    router.post('/risk-rules',requireLogin,requireCsrf,writeRateLimiter,async(req,res)=>{
        let rule;
        try{rule=normalizeRule(req.body);}catch(error){return res.status(400).json({success:false,message:error.message});}
        const userId=req.session.user.id;
        const conn=await pool.getConnection();
        try {
            await conn.beginTransaction();
            // 序列化同會員的寫入，避免突破規則數量限制。
            await conn.execute('SELECT id FROM users WHERE id=? FOR UPDATE',[userId]);
            if(rule.emailEnabled){
                const [profiles]=await conn.execute('SELECT verified_at FROM notification_profiles WHERE user_id=?',[userId]);
                if(!profiles[0]?.verified_at){await conn.rollback();return res.status(400).json({success:false,message:'請先驗證通知信箱'});}
                if(process.env.EMAIL_ENABLED!=='true'){await conn.rollback();return res.status(503).json({success:false,message:'寄信服務尚未啟用'});}
            }
            const [existing]=await conn.execute('SELECT id FROM alert_rules WHERE user_id=? AND client_uid=?',[userId,rule.uid]);
            if(existing.length){
                await conn.execute('UPDATE alert_rules SET symbol=?,market=?,config=?,email_enabled=?,version=version+1 WHERE id=?',[rule.symbol,rule.market,JSON.stringify(rule),rule.emailEnabled,existing[0].id]);
                await conn.execute("UPDATE alert_events SET email_state='cancelled' WHERE rule_id=? AND email_state IN ('pending','sending')",[existing[0].id]);
            } else {
                const [count]=await conn.execute('SELECT COUNT(*) AS total FROM alert_rules WHERE user_id=?',[userId]);
                if(count[0].total>=100){await conn.rollback();return res.status(400).json({success:false,message:'最多可儲存 100 筆提醒'});}
                await conn.execute('INSERT INTO alert_rules (user_id,client_uid,symbol,market,config,email_enabled) VALUES (?,?,?,?,?,?)',[userId,rule.uid,rule.symbol,rule.market,JSON.stringify(rule),rule.emailEnabled]);
            }
            await conn.commit();res.json({success:true,rules:await getRules(userId),message:'提醒已儲存至伺服器'});
        }catch(error){await conn.rollback();throw error;}finally{conn.release();}
    });
    router.delete('/risk-rules/:uid',requireLogin,requireCsrf,writeRateLimiter,async(req,res)=>{
        await pool.execute('DELETE FROM alert_rules WHERE user_id=? AND client_uid=?',[req.session.user.id,req.params.uid]);
        res.json({success:true,rules:await getRules(req.session.user.id)});
    });
    router.get('/risk-notifications',requireLogin,async(req,res)=>{
        const [rows]=await pool.execute('SELECT id,content,unread,email_state,created_at FROM alert_events WHERE user_id=? AND hidden=FALSE ORDER BY id DESC LIMIT 100',[req.session.user.id]);
        res.json({success:true,notifications:rows.map(row=>({...parseContent(row),id:String(row.id),unread:Boolean(row.unread),emailState:row.email_state}))});
    });
    router.post('/risk-notifications',requireLogin,requireCsrf,writeRateLimiter,async(req,res)=>{
        if(req.body.action==='clear')await pool.execute('UPDATE alert_events SET hidden=TRUE,unread=FALSE WHERE user_id=?',[req.session.user.id]);
        else if(req.body.action==='read'){
            const id=req.body.id ? String(req.body.id) : null;
            if(id&&!/^\d+$/.test(id))return res.status(400).json({success:false,message:'通知代號無效'});
            await pool.execute('UPDATE alert_events SET unread=FALSE WHERE user_id=?'+(id?' AND id=?':''),id?[req.session.user.id,id]:[req.session.user.id]);
        } else return res.status(400).json({success:false,message:'操作無效'});
        res.json({success:true});
    });
    router.use((error,req,res,next)=>{
        console.error('[提醒服務] 錯誤：',error.code || '處理失敗');
        res.status(503).json({success:false,message:'提醒服務暫時無法使用'});
    });

    async function fetchQuotes(stocks) {
        const quotes=new Map();
        for(let i=0;i<stocks.length;i+=50){
            const params=new URLSearchParams({ex_ch:stocks.slice(i,i+50).map(s=>`${s.market}_${s.symbol}.tw`).join('|'),json:'1',delay:'0',_:String(Date.now())});
            const response=await fetch(`https://mis.twse.com.tw/stock/api/getStockInfo.jsp?${params}`,{timeout:10000,headers:{'User-Agent':'Mozilla/5.0',Referer:'https://mis.twse.com.tw/stock/fibest.jsp'}});
            if(!response.ok)throw new Error('StockAPI 回應失敗');
            const data=await response.json();
            if(data.rtcode!=='0000'||!Array.isArray(data.msgArray))throw new Error('StockAPI 資料異常');
            for(const item of data.msgArray){const quote=readLiveQuote(item);if(quote)quotes.set(`${item.ex}:${item.c}`,quote);}
        }
        return quotes;
    }
    async function processEvents(rows,quotes){
        for(const row of rows){
            const rule=parseConfig(row);const quote=quotes.get(`${row.market}:${row.symbol}`);
            for(const type of triggeredTypes(rule,quote)){
                const title={stop:'停損提醒',profit:'獲利提醒',time:'提醒期限已到'}[type];
                const message=type==='time'?`${rule.symbol} ${rule.name} 的提醒期限已到。`:`${rule.symbol} ${quote.name || rule.name} 已達${type==='stop'?'停損':'獲利'}條件；來源成交價 ${quote.price} 元，報價時間 ${quote.time}。`;
                const content={ruleUid:rule.uid,symbol:rule.symbol,name:quote?.name || rule.name,type,title,message,createdAt:new Date().toISOString(),quoteTime:quote?.time || 0};
                const recipient=row.email_enabled&&row.verified_at?row.email:null;
                await pool.execute('INSERT IGNORE INTO alert_events (user_id,rule_id,rule_version,event_type,content,email,email_state) VALUES (?,?,?,?,?,?,?)',[row.user_id,row.id,row.version,type,JSON.stringify(content),recipient,recipient?'pending':'skipped']);
            }
        }
    }
    async function deliverEvents(){
        if(process.env.EMAIL_ENABLED!=='true')return;
        const [events]=await pool.query("SELECT e.*,p.verified_at,p.email AS current_email,r.email_enabled,r.version AS current_version FROM alert_events e JOIN notification_profiles p ON p.user_id=e.user_id JOIN alert_rules r ON r.id=e.rule_id WHERE e.email_state='pending' AND e.next_attempt<=? ORDER BY e.id LIMIT 10",[Date.now()]);
        for(const event of events){
            if(!event.verified_at||event.current_email!==event.email||!event.email_enabled||event.current_version!==event.rule_version){await pool.execute("UPDATE alert_events SET email_state='cancelled' WHERE id=?",[event.id]);continue;}
            const [claim]=await pool.execute("UPDATE alert_events SET email_state='sending',attempts=attempts+1,next_attempt=? WHERE id=? AND email_state='pending'",[Date.now()+60000,event.id]);
            if(!claim.affectedRows)continue;
            const content=parseContent(event);
            try{
                await mailer.sendMail({to:event.email,subject:`古估股｜${content.title} ${content.symbol}`,text:`${content.message}\n\n請登入古估股查看或調整提醒。每筆規則的同一觸發條件只提醒一次；修改規則後重新監控。\n${process.env.APP_URL || 'http://localhost/gugugu'}\n\n此信為條件提醒，並非投資建議。`,messageId:`<gugugu-alert-${event.id}@${process.env.MAIL_FROM_ADDRESS.split('@')[1]}>`});
                await pool.execute("UPDATE alert_events SET email_state='sent' WHERE id=? AND email_state='sending'",[event.id]);
            }catch(error){
                const attempts=Number(event.attempts)+1;
                await pool.execute('UPDATE alert_events SET email_state=?,next_attempt=? WHERE id=? AND email_state=\'sending\'',[attempts>=5?'failed':'pending',Date.now()+60000*Math.pow(2,attempts),event.id]);
                console.error('[提醒寄信] 失敗：',error.code || 'SMTP 錯誤');
            }
        }
    }
    async function tick(){
        if(running)return;running=true;
        let lock;
        let locked=false;
        try{
            lock=await pool.getConnection();
            const [locks]=await lock.query("SELECT GET_LOCK('gugugu_reminder_worker',0) AS acquired");locked=Boolean(locks[0].acquired);if(!locked)return;
            await pool.execute("UPDATE alert_events SET email_state='pending' WHERE email_state='sending' AND next_attempt<?",[Date.now()]);
            const [rows]=await pool.query('SELECT r.*,p.email,p.verified_at FROM alert_rules r LEFT JOIN notification_profiles p ON p.user_id=r.user_id ORDER BY r.id');
            const stocks=[...new Map(rows.map(r=>[`${r.market}:${r.symbol}`,{market:r.market,symbol:r.symbol}])).values()];
            let quotes=new Map();
            if(stocks.length){try{quotes=await fetchQuotes(stocks);}catch(error){console.error('[提醒行情] StockAPI 失敗，跳過價格觸發：',error.message);}}
            latestQuotes=quotes;
            if(stocks.length && quotes.size<stocks.length) console.warn('[提醒行情] 缺少或過期報價以 0 顯示，不觸發價格提醒。');
            await processEvents(rows,quotes);await deliverEvents();
            await pool.execute('DELETE FROM email_verifications WHERE expires_at<?',[Date.now()]);
        }catch(error){console.error('[提醒監控] 失敗：',error.code || '處理錯誤');}
        finally{try{if(locked)await lock.query("SELECT RELEASE_LOCK('gugugu_reminder_worker')");}finally{if(lock)lock.release();running=false;}}
    }
    function start(){tick();const timer=setInterval(tick,30000);timer.unref();return timer;}
    return {router,initialize,start,tick,processEvents,getRules};
}
module.exports={createReminders,normalizeRule,readLiveQuote,triggeredTypes,hashToken};
