/* ===== 全域搜尋腳本 ===== */
document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('header-search-input');
  const searchButton = document.getElementById('header-search-button');
  if (!searchInput || !searchButton || searchInput.dataset.searchBound) return;
  searchInput.dataset.searchBound = 'true';
  const siteBase = document.querySelector('.site-header')?.dataset.base;
  let searchDatabase = [];
  let dataLoaded = false;

  async function loadSearchData() {
    // 檢查是否已經載入過，避免重複載入
    if (dataLoaded) return;
    
    try {
      // 計算相對路徑：根據當前頁面位置來決定資料夾路徑
      const dataPath = siteBase !== undefined ? `${siteBase}/data/` : (location.pathname.includes('/lists/') || location.pathname.includes('/gugu_chapter/') ? '../data/' : 'data/');

      const foodUrl = dataPath + 'food-data.json';
      const travelUrl = dataPath + 'travel-data.json';
      const aviationUrl = dataPath + 'aviation-data.json';
      
      console.log('🔍 當前路徑:', location.pathname);
      console.log('📡 搜尋資料庫載入位置:', { dataPath, foodUrl, travelUrl, aviationUrl });

      // 1. 同時抓取所有類別的 JSON 檔案
      const [foodRes, travelRes, aviationRes] = await Promise.all([
        fetch(foodUrl),
        fetch(travelUrl),
        fetch(aviationUrl)
      ]);

      // 檢查每個回應是否成功
      if (!foodRes.ok) throw new Error(`food-data.json 載入失敗: ${foodRes.status} (${foodUrl})`);
      if (!travelRes.ok) throw new Error(`travel-data.json 載入失敗: ${travelRes.status} (${travelUrl})`);
      if (!aviationRes.ok) throw new Error(`aviation-data.json 載入失敗: ${aviationRes.status} (${aviationUrl})`);

      const foodData = await foodRes.json();
      const travelData = await travelRes.json();
      const aviationData = await aviationRes.json();

      // 2. 為每個項目加上 'category' 屬性，以便跳轉
      const foodItems = foodData.map(item => ({ ...item, category: 'food' }));
      const travelItems = travelData.map(item => ({ ...item, category: 'travel' }));
      const aviationItems = aviationData.map(item => ({ ...item, category: 'aviation' }));

      // 3. 合併成一個完整的搜尋資料庫
      searchDatabase = [...foodItems, ...travelItems, ...aviationItems];
      dataLoaded = true;
      console.log('✓ 搜尋資料庫載入完成，共', searchDatabase.length, '筆資料');
      console.log('  Food:', foodItems.length, '| Travel:', travelItems.length, '| Aviation:', aviationItems.length);

    } catch (error) {
      console.error('✗ 載入搜尋資料庫失敗:', error);
      // 標記為已嘗試，但不更新成功狀態
      dataLoaded = false; // 允許重試
    }
  }

  async function performSearch() {
    const query = searchInput.value.trim();
    if (!query) return; // 如果是空的，不做任何事

    if (!dataLoaded) await loadSearchData();
    if (!dataLoaded) { alert('股票搜尋資料暫時無法讀取，請稍後重試。'); return; }
    const normalizedQuery = query.toUpperCase();

    // 4. 在資料庫中尋找
    const result = searchDatabase.find(item => {
      // 允許搜尋中文名稱 (e.g., "長榮航")
      const nameMatch = item.name.toUpperCase().includes(normalizedQuery);
      // 允許搜尋代碼 (e.g., "2618" 或 "2618.TW")
      const symbolMatch = item.symbol.toUpperCase().startsWith(normalizedQuery.replace('.TW', ''));
      return nameMatch || symbolMatch;
    });

    // 5. 根據結果執行動作
    if (result) {
      // 找到了！跳轉到該股票的詳細頁面
      window.location.href = `${siteBase ?? '/gugugu'}/stock-detail.php?category=${result.category}&symbol=${result.symbol}`;
    } else {
      // 找不到
      alert(`在我們的資料庫中找不到：${query}\n\n請確認名稱或代碼是否正確。`);
    }
  }

  // 6. 綁定事件
  if (searchButton && searchInput) {
    // 點擊按鈕時搜尋
    searchButton.addEventListener('click', performSearch);
    
    // 按下 Enter 鍵時也搜尋
    searchInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        performSearch();
      }
    });

    // 點擊輸入框時，預先載入資料
    searchInput.addEventListener('focus', loadSearchData, { once: true });
  }

  // 頁面載入時先把搜尋資料抓好，避免直接按搜尋時還沒載入完成
  loadSearchData();
});