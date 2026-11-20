const crypto=require('crypto');
const bcrypt=require('bcryptjs');
const mailer=require('./mailer');
const hash=value=>crypto.createHash('sha256').update(value).digest('hex');
const validEmail=email=>email.length<=254 && /^[A-Za-z0-9.!#$%&'*+/=?^_`{|}~-]+@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)+$/.test(email);
function recoveryLink(page,token) {
 const base=new URL(process.env.APP_URL || 'http://localhost/gugugu/');
 if(base.protocol!=='https:' && !(base.protocol==='http:' && ['localhost','127.0.0.1'].includes(base.hostname)))throw Error('正式網站須使用 HTTPS');
 const url=new URL(page,base.href.replace(/\/?$/,'/'));url.hash=`token=${token}`;return url.href;
}
function createRecovery({pool,limiter,sendMail=mailer.sendMail}) {
 const router=require('express').Router();
 async function initialize(){await pool.query(`CREATE TABLE IF NOT EXISTS password_resets (token_hash CHAR(64) PRIMARY KEY,user_id BIGINT UNSIGNED NOT NULL,email VARCHAR(254) NOT NULL,expires_at BIGINT NOT NULL,created_at BIGINT NOT NULL,INDEX(user_id),FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`);}
 router.post('/forgot',limiter,async(req,res)=>{
  const username=String(req.body?.username || '').trim();
  if(!username)return res.status(400).json({success:false,message:'請輸入帳號'});
  const message='若帳號已綁定驗證信箱，將寄送密碼重設連結，請查看收件匣與垃圾郵件；未綁定者請向古估股專題團隊申請協助。';
  const conn=await pool.getConnection();
  try{
   await conn.beginTransaction();
   const [rows]=await conn.execute('SELECT u.id,p.email,p.verified_at FROM users u LEFT JOIN notification_profiles p ON p.user_id=u.id WHERE u.username COLLATE utf8mb4_unicode_ci=? FOR UPDATE',[username]);const user=rows[0];
   if(user?.verified_at && mailer.configured() && process.env.EMAIL_ENABLED==='true'){
    const [recent]=await conn.execute('SELECT created_at FROM password_resets WHERE user_id=? ORDER BY created_at DESC LIMIT 1',[user.id]);
    if(!recent.length || Date.now()-Number(recent[0].created_at)>=60000){
     const token=crypto.randomBytes(32).toString('hex');const link=recoveryLink('reset-password.php',token);
     await conn.execute('DELETE FROM password_resets WHERE user_id=?',[user.id]);
     await conn.execute('INSERT INTO password_resets VALUES (?,?,?,?,?)',[hash(token),user.id,user.email,Date.now()+30*60000,Date.now()]);
     await sendMail({to:user.email,subject:'古估股｜重設密碼',text:`你已申請重設古估股密碼。請在 30 分鐘內開啟以下連結設定新密碼：\n${link}\n若不是你提出的申請，請忽略此信，你的密碼不會變更。`});
    }
   }
   await conn.commit();
  }catch(error){await conn.rollback();console.warn('[密碼重設] 申請失敗：',error.code || '寄信服務錯誤');}
  finally{conn.release();}
  res.json({success:true,message});
 });
 router.post('/reset-password',limiter,async(req,res)=>{
  const token=String(req.body?.token || ''),password=String(req.body?.password || '');
  if(!/^[a-f0-9]{64}$/.test(token))return res.status(400).json({success:false,message:'重設連結無效或已過期，請重新申請'});
  const policyError=require('./account-policy').passwordError(password);
  if(policyError)return res.status(400).json({success:false,message:policyError});
  const passwordHash=await bcrypt.hash(password,12);const conn=await pool.getConnection();
  try{
   await conn.beginTransaction();
   // 與信箱變更及其他重設請求共同鎖定使用者，避免重複消耗。
   const [owners]=await conn.execute('SELECT user_id FROM password_resets WHERE token_hash=?',[hash(token)]);
   if(!owners.length)throw Error('invalid');
   const [accounts]=await conn.execute('SELECT id,username FROM users WHERE id=? FOR UPDATE',[owners[0].user_id]);
   const accountError=require('./account-policy').passwordError(password,accounts[0]?.username);
   if(accountError){await conn.rollback();return res.status(400).json({success:false,message:accountError});}
   const [rows]=await conn.execute('SELECT r.user_id FROM password_resets r JOIN notification_profiles p ON p.user_id=r.user_id WHERE r.token_hash=? AND r.expires_at>? AND p.verified_at IS NOT NULL AND p.email=r.email FOR UPDATE',[hash(token),Date.now()]);
   if(!rows.length)throw Error('invalid');
   await conn.execute('UPDATE users SET password_hash=? WHERE id=?',[passwordHash,rows[0].user_id]);
   await conn.execute('DELETE FROM password_resets WHERE user_id=?',[rows[0].user_id]);
   await conn.commit();
   const store=req.sessionStore;
   if(store?.all)await new Promise(resolve=>store.all((error,sessions)=>{
    if(error){console.warn('[密碼重設] 登入狀態清理失敗');resolve();return;}
    const ids=Object.entries(sessions || {}).filter(([,session])=>String(session.user?.id)===String(rows[0].user_id)).map(([id])=>id);
    Promise.all(ids.map(id=>new Promise(done=>store.destroy(id,()=>done())))).then(resolve);
   }));
   return res.json({success:true,message:'密碼已重設，請使用新密碼重新登入'});
  }catch(error){await conn.rollback();return res.status(error.message==='invalid'?400:503).json({success:false,message:error.message==='invalid'?'重設連結無效或已過期，請重新申請':'密碼重設服務暫時無法使用'});}
  finally{conn.release();}
 });
 return {router,initialize};
}
module.exports={createRecovery,validEmail,recoveryLink};
