(() => {
  let tickerPromise;
  window.initializeSiteTicker = () => {
    if (tickerPromise) return tickerPromise;
    tickerPromise = (async () => {
      const target = document.getElementById('ticker-content');
      if (!target) return;
      const api = typeof PROXY_URL !== 'undefined' ? PROXY_URL : (document.querySelector('meta[name="gugugu-api-base"]')?.content ?? (['localhost','127.0.0.1'].includes(location.hostname) && location.port !== '8088' ? `${location.protocol}//${location.hostname}:4000` : ''));
      try {
        const response = await fetch(`${api}/api/news`, { signal: AbortSignal.timeout(10000) });
        if (!response.ok) throw new Error('新聞服務回應失敗');
        const data = await response.json();
        if (!data.success || !Array.isArray(data.items)) throw new Error('新聞來源格式異常');
        const items = data.items;
        target.title = data.updatedAt ? `最後更新：${new Date(data.updatedAt).toLocaleString('zh-TW', {timeZone:'Asia/Taipei',hour12:false})}${data.stale ? '（部分來源暫未更新）' : ''}` : '';
        const fragment = document.createDocumentFragment();
        for (const item of [...items, ...items]) {
          const url = new URL(item.link);
          if (url.protocol !== 'https:') continue;
          const link = document.createElement('a');
          link.href = url.href; link.target = '_blank'; link.rel = 'noopener noreferrer';
          link.className = 'ticker-item'; link.textContent = `【${item.categoryName}】${String(item.title || '')}`;
          fragment.append(link);
        }
        target.replaceChildren(fragment);
        if (!target.children.length) target.textContent = '新聞暫時無法讀取';
        else target.classList.remove('ticker-status');
      } catch (error) { target.textContent = '新聞暫時無法讀取'; console.warn('新聞跑馬燈：', error.message); }
    })();
    return tickerPromise;
  };
  document.addEventListener('DOMContentLoaded', async () => {
    const header = document.querySelector('.site-header');
    if (!header) return;
    window.initializeSiteTicker();
    const menuButton = header.querySelector('.mobile-nav-toggle');
    menuButton.addEventListener('click', () => {
      const open = menuButton.getAttribute('aria-expanded') !== 'true';
      menuButton.setAttribute('aria-expanded', String(open));
      header.querySelector('.main-nav').classList.toggle('mobile-open', open);
      header.querySelector('.header-right').classList.toggle('mobile-open', open);
    });
    const api = typeof PROXY_URL !== 'undefined' ? PROXY_URL : (document.querySelector('meta[name="gugugu-api-base"]')?.content ?? (['localhost','127.0.0.1'].includes(location.hostname) && location.port !== '8088' ? `${location.protocol}//${location.hostname}:4000` : ''));
    const logout = document.getElementById('logoutBtn');
    const login = header.querySelector('.site-login-link');
    logout.addEventListener('click', async () => {
      logout.disabled = true;
      try {
        const tokenResponse = await fetch(`${api}/api/csrf`, { credentials: 'include', cache: 'no-store' });
        const token = await tokenResponse.json();
        if (!tokenResponse.ok || !token.csrfToken) throw new Error('無法取得登出驗證，請重試');
        const response = await fetch(`${api}/api/logout`, { method: 'POST', credentials: 'include', headers: { 'X-CSRF-Token': token.csrfToken } });
        if (!response.ok) throw new Error('登出未完成，請重試');
        localStorage.removeItem('currentUser');
        location.href = `${header.dataset.base}/login.php`;
      } catch (error) { alert(error.message || '登出服務暫時無法使用'); }
      finally { logout.disabled = false; }
    });
    try {
      const response = await fetch(`${api}/api/status`, { credentials: 'include', cache: 'no-store' });
      if (!response.ok) throw new Error('登入狀態讀取失敗');
      const data = await response.json();
      if (data.isLoggedIn) {
        document.getElementById('userInfo').textContent = `Hi, ${data.user?.name || data.user?.username || '使用者'}`;
        logout.style.display = 'inline-block'; login.hidden = true;
      } else { document.getElementById('userInfo').textContent = '訪客'; logout.style.display = 'none'; }
    } catch (error) { document.getElementById('userInfo').textContent = '登入狀態暫時無法讀取'; }
  });
})();
