<!doctype html>
<html lang="zh-TW"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="referrer" content="no-referrer"><title>古估股｜重設密碼</title><style>body{background:#0d1020;color:#eee;font-family:system-ui;margin:0;padding:24px}main{max-width:420px;margin:10vh auto;background:#191f34;padding:30px;border-radius:18px}input,button{box-sizing:border-box;width:100%;padding:14px;margin:10px 0;border-radius:8px}button{background:#64ffda;border:0;cursor:pointer}a{color:#64ffda}#status{line-height:1.7}</style></head><body><main><h1>設定新密碼</h1><p>連結有效期限為 30 分鐘，每個連結只能使用一次。</p><p>密碼最多 72 個 UTF-8 位元組，首字須為大寫英文，並包含數字與符號；不得有空白、使用常見密碼或與帳號相同。</p><form id="resetForm"><label for="newPassword">新密碼（至少 8 個字元）</label><input id="newPassword" type="password" autocomplete="new-password" minlength="8" required><label for="confirmPassword">再次輸入新密碼</label><input id="confirmPassword" type="password" autocomplete="new-password" minlength="8" required><button id="save">確認重設密碼</button></form><p id="status" role="status" aria-live="polite"></p><a href="login.php">返回登入</a></main><script>
const token=new URLSearchParams(location.hash.slice(1)).get('token') || '';
history.replaceState(null,'',location.pathname);
const form=document.getElementById('resetForm'),status=document.getElementById('status'),save=document.getElementById('save');
if(!/^[a-f0-9]{64}$/.test(token)){form.hidden=true;status.textContent='連結無效，請返回登入頁重新申請。';}
form.addEventListener('submit',async event=>{
 event.preventDefault();const password=document.getElementById('newPassword').value;
 if(password!==document.getElementById('confirmPassword').value){status.textContent='兩次輸入的密碼不一致。';return;}
 save.disabled=true;status.textContent='正在重設密碼…';
 try{const api=['localhost','127.0.0.1'].includes(location.hostname) && location.port !== '8088'?`${location.protocol}//${location.hostname}:4000`:'';
 const response=await fetch(`${api}/api/reset-password`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({token,password})});
 const data=await response.json();status.textContent=data.message;
 if(response.ok && data.success)form.hidden=true;
 }catch{status.textContent='無法連線到重設服務，請稍後再試。';}finally{save.disabled=false;}
});
</script></body></html>
