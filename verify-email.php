<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="referrer" content="no-referrer">
<title>驗證通知信箱 - 古估股</title>
<link rel="stylesheet" href="styles/style.css">
<style>.verify-box{max-width:560px;margin:80px auto;padding:32px;background:#1a1c2a;border:1px solid #3a3d5a;border-radius:16px;line-height:1.8}.verify-box button{padding:12px 22px;background:#64ffda;border:0;border-radius:8px;cursor:pointer}.verify-box a{color:#64ffda}</style>
</head>
<body><main class="verify-box"><h1>驗證通知信箱</h1><p id="verifyMessage" role="status">按下確認，即可將此信箱用於你申請的古估股提醒。</p><button id="verifyButton" type="button">確認驗證</button><p><a href="main.php">返回首頁</a></p></main>
<script>
const token=new URLSearchParams(location.hash.slice(1)).get('token');
history.replaceState(null,'',location.pathname);
const button=document.getElementById('verifyButton'),message=document.getElementById('verifyMessage');
if(!token){button.disabled=true;message.textContent='缺少驗證連結，請回首頁重新寄送驗證信。';}
button.addEventListener('click',async()=>{
 button.disabled=true;
 try{
  const api=document.querySelector('meta[name="gugugu-api-base"]')?.content ?? (['localhost','127.0.0.1'].includes(location.hostname) && location.port !== '8088'?`${location.protocol}//${location.hostname}:4000`:'');
  const response=await fetch(`${api}/api/verify-email`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({token})});
  const result=await response.json();message.textContent=result.message;
  if(result.success)button.hidden=true;else button.disabled=false;
 }catch(error){message.textContent='驗證服務暫時無法使用，請稍後重試。';button.disabled=false;}
});
</script></body></html>
