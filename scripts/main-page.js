// Main page specific logic: auth gate, UI bootstrap, and stock reverse lookup modal.

document.addEventListener('DOMContentLoaded', async () => {
  // 登入狀態只信任 Node.js 後端 Session，不能由 localStorage 偽造。
  const isLoggedIn = await checkLoginStatus();
  if (!isLoggedIn) return;

  // 先載入帳號收藏，讓首次繪製與重新整理後的星星保持正確狀態。
  await syncFavorites();

  if (typeof initializeUICards === 'function') initializeUICards();
  if (typeof initTicker === 'function') initTicker();
  if (typeof updateAllTables === 'function') updateAllTables();
  if (typeof initAgencySearch === 'function') initAgencySearch();

  setInterval(() => {
    if (typeof updateAllTables === 'function') updateAllTables();
  }, 10000);
});

// 瀏覽器上一頁可能直接還原快取，需重新同步離開期間變更的收藏。
window.addEventListener('pageshow', async (event) => {
  if (!event.persisted) return;
  if (!await checkLoginStatus()) return;
  await syncFavorites();
  if (typeof updateAllTables === 'function') await updateAllTables();
});

function openStockReverseLookup(stockId, stockName) {
  const modal = document.getElementById('stockReverseModal');
  const listBody = document.getElementById('reverseEtfList');
  const titleName = document.getElementById('currentStockName');
  const emptyMsg = document.getElementById('reverseEmptyMsg');

  if (!modal || !listBody || !titleName) return;

  titleName.textContent = `${stockId} ${stockName}`;
  listBody.innerHTML = '';

  if (emptyMsg) {
    emptyMsg.textContent = '目前沒有可驗證的 ETF 成分股資料。';
    emptyMsg.style.display = 'block';
  }

  modal.style.display = 'flex';
}

document.addEventListener('click', (e) => {
  const row = e.target.closest('tr');
  if (row && row.closest('.stock-table tbody')) {
    const cells = row.cells;
    if (cells.length >= 2) {
      openStockReverseLookup(cells[0].innerText.trim(), cells[1].innerText.trim());
    }
  }
});
