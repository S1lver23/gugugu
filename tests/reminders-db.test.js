const test=require('node:test');const assert=require('node:assert/strict');const crypto=require('crypto');
const express=require('express');const {pool}=require('../backend/database');
const {createReminders,hashToken}=require('../backend/reminders');
const mailer=require('../backend/mailer');

test('會員隔離、CSRF、信箱驗證、通知去重與清空後不重觸發', {skip:process.env.DB_INTEGRATION!=='1'},async()=>{
 const ids=[];let server;const originalSend=mailer.sendMail;const originalEnabled=process.env.EMAIL_ENABLED;let captured=[];
 // 測試時攔截寄信，絕不向外部收件人寄出測試信。
 mailer.sendMail=async message=>{captured.push(message);return {accepted:[message.to]};};process.env.EMAIL_ENABLED='true';
 try{
  for(let i=0;i<2;i++){
   const [r]=await pool.execute('INSERT INTO users (username,password_hash) VALUES (?,?)',['test_'+crypto.randomBytes(6).toString('hex'),'integration-only']);ids.push(r.insertId);
  }
  const requireLogin=(req,res,next)=>req.session.user?next():res.status(401).json({success:false});
  const requireCsrf=(req,res,next)=>req.get('x-csrf-token')==='test-csrf'?next():res.status(403).json({success:false});
  const service=createReminders({pool,requireLogin,requireCsrf,writeRateLimiter:(req,res,next)=>next(),emailRateLimiter:(req,res,next)=>next()});
  await service.initialize();const app=express();app.use(express.json());
  app.use((req,res,next)=>{const id=Number(req.get('x-test-user'));req.session={user:ids.includes(id)?{id}:null};next();});app.use('/api',service.router);
  server=app.listen(0,'127.0.0.1');await new Promise(resolve=>server.once('listening',resolve));
  const request=async(path,user,body,csrf=true)=>{const response=await fetch(`http://127.0.0.1:${server.address().port}/api/${path}`,{method:body?'POST':'GET',headers:{'Content-Type':'application/json','x-test-user':String(user),...(csrf?{'x-csrf-token':'test-csrf'}:{})},...(body?{body:JSON.stringify(body)}:{})});return {status:response.status,data:await response.json()};};
  const config={uid:'owned',symbol:'2705',market:'tse',name:'六福',deadline:'2026-10-14T13:00:00+08:00',emailEnabled:false};
  assert.equal((await request('risk-rules',ids[0],config,false)).status,403);
  assert.equal((await request('risk-rules',ids[0],config)).status,200);
  assert.equal((await request('risk-rules',ids[1])).data.rules.length,0);
  assert.equal((await request('risk-rules',ids[0],{...config,emailEnabled:true})).status,400);
  assert.equal((await request('email-settings',ids[0],{email:'notification-test@example.com'})).status,200);
  assert.equal(captured.length,1);
  const link=captured[0].text.match(/https?:\/\/[^\s]+/)[0];const token=new URLSearchParams(new URL(link).hash.slice(1)).get('token');
  assert(token);const [tokens]=await pool.execute('SELECT token_hash FROM email_verifications WHERE user_id=?',[ids[0]]);assert.equal(tokens[0].token_hash,hashToken(token));assert.notEqual(tokens[0].token_hash,token);
  assert.equal((await request('verify-email',0,{token})).status,200);
  assert.equal((await request('verify-email',0,{token})).status,400);
  assert.equal((await request('email-settings',ids[0])).data.verified,true);
  // 改信箱時不能繼承先前的驗證狀態。
  await pool.execute('UPDATE notification_profiles SET last_sent_at=0 WHERE user_id=?',[ids[0]]);
  assert.equal((await request('email-settings',ids[0],{email:'replacement-test@example.com'})).status,200);
  assert.equal((await request('email-settings',ids[0])).data.verified,false);
  const [rows]=await pool.execute('SELECT * FROM alert_rules WHERE user_id=?',[ids[0]]);
  await service.processEvents(rows,new Map());await service.processEvents(rows,new Map());
  let result=await request('risk-notifications',ids[0]);assert.equal(result.data.notifications.length,1);
  assert.equal((await request('risk-notifications',ids[1])).data.notifications.length,0);
  await request('risk-notifications',ids[0],{action:'clear'});await service.processEvents(rows,new Map());
  assert.equal((await request('risk-notifications',ids[0])).data.notifications.length,0);
  const [events]=await pool.execute('SELECT COUNT(*) AS count FROM alert_events WHERE user_id=?',[ids[0]]);assert.equal(events[0].count,1);
 }finally{
  mailer.sendMail=originalSend;process.env.EMAIL_ENABLED=originalEnabled;
  for(const id of ids)await pool.execute('DELETE FROM users WHERE id=?',[id]);
  if(server)await new Promise(resolve=>server.close(resolve));
 }
});
test.after(()=>pool.end());
