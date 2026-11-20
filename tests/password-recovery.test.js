const test=require('node:test'),assert=require('node:assert/strict'),express=require('express'),bcrypt=require('bcryptjs');
const {pool}=require('../backend/database');const {createRecovery}=require('../backend/password-recovery');
test('重設僅寄已驗證信箱，密碼更新、連結重放與信箱變更阻擋',async()=>{
 const old=process.env.EMAIL_ENABLED;process.env.EMAIL_ENABLED='true';process.env.SMTP_HOST ||= 'test';process.env.SMTP_USER ||= 'test';process.env.SMTP_PASSWORD ||= 'test';process.env.MAIL_FROM_ADDRESS ||= 'test@example.com';
 const name='reset_test_'+Date.now();let userId,server,mails=[];
 const recovery=createRecovery({pool,limiter:(req,res,next)=>next(),sendMail:async mail=>mails.push(mail)});
 try{await recovery.initialize();const [r]=await pool.execute('INSERT INTO users(username,password_hash) VALUES (?,?)',[name,await bcrypt.hash('oldpassword',4)]);userId=r.insertId;
 await pool.execute('INSERT INTO notification_profiles(user_id,email) VALUES (?,?)',[userId,'test@example.com']);
 const app=express();app.use(express.json());app.use('/api',recovery.router);server=app.listen(0,'127.0.0.1');await new Promise(r=>server.once('listening',r));const base=`http://127.0.0.1:${server.address().port}/api`;
 const post=async(route,body)=>{const r=await fetch(base+route,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});return {status:r.status,data:await r.json()};};
 const unverified=await post('/forgot',{username:name});assert.equal(mails.length,0);const unknown=await post('/forgot',{username:name+'x'});assert.deepEqual(unverified,unknown);
 await pool.execute('UPDATE notification_profiles SET verified_at=UTC_TIMESTAMP() WHERE user_id=?',[userId]);await post('/forgot',{username:name});assert.equal(mails.length,1);const token=mails[0].text.match(/token=([a-f0-9]{64})/)[1];
 assert.equal((await post('/reset-password',{token,password:'NewSecret!928'})).status,200);assert.equal((await post('/reset-password',{token,password:'NewSecret!928'})).status,400);
 const [rows]=await pool.execute('SELECT password_hash FROM users WHERE id=?',[userId]);assert(await bcrypt.compare('NewSecret!928',rows[0].password_hash));
 await post('/forgot',{username:name});const second=mails[1].text.match(/token=([a-f0-9]{64})/)[1];await pool.execute('UPDATE notification_profiles SET email=? WHERE user_id=?',['changed@example.com',userId]);assert.equal((await post('/reset-password',{token:second,password:'NextSecret!739'})).status,400);
 }finally{if(server)await new Promise(r=>server.close(r));if(userId)await pool.execute('DELETE FROM users WHERE id=?',[userId]);process.env.EMAIL_ENABLED=old;await pool.end();}
});
