function escapeStockText(value) { return String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
﻿// ==========================================
// 1. 設定與資料定義 (Configs & Data)
// ==========================================
// 本機直連 Node；公開網址透過同源入口轉送 API。
// 由腳本位置取得網站根路徑，兼容 XAMPP 子目錄與 Tunnel。
const SITE_ROOT_URL = new URL('../', document.currentScript.src);
const PROXY_URL = ['localhost', '127.0.0.1'].includes(location.hostname) && location.port !== '8088' ? `${location.protocol}//${location.hostname}:4000` : '';

const REFRESH_MS = 5000; 
const MAX_POINTS = 120; 


// ✨ 新增：存放目前使用者的收藏清單 (例如: ["2618", "2727"])
let userFavorites = [];
let csrfToken = null;

// 上方 ETF 卡片清單
const etfs = {
  '00905.TW': { name: 'FT台灣Smart' },
  '006204.TW': { name: '永豐台灣加權' },
  '00921.TW': { name: '兆豐龍頭等權重' },
  '00730.TW': { name: '富邦台灣優質高息' },
  '009803.TW': { name: '玉山市值動能50' }, 
  '00980A.TW': { name: '主動野村台灣優選' }
};

// 下方個股清單 (美食/旅遊/航空)
const foodStocks = [
  { id: "2727", name: "王品集團", type: "tse" }, { id: "2723", name: "美食-KY", type: "tse" },
  { id: "2753", name: "八方雲集", type: "tse" }, { id: "2729", name: "瓦城", type: "otc" },
  { id: "7705", name: "三商餐飲", type: "tse" }, { id: "7708", name: "全家餐飲", type: "otc" },
  { id: "1268", name: "漢來美食", type: "otc" }
];
const travelStocks = [
  { id: "2707", name: "晶華", type: "tse" }, { id: "2705", name: "六福", type: "tse" },
  { id: "5706", name: "鳳凰", type: "tse" }, { id: "2731", name: "雄獅", type: "tse" },
  { id: "5701", name: "劍湖山", type: "otc" }, { id: "2712", name: "遠雄來", type: "tse" },
  { id: "2722", name: "夏都", type: "tse" }, { id: "2748", name: "雲品", type: "tse" }
];
const aviationStocks = [
  { id: "2618", name: "長榮航", type: "tse" }, { id: "2610", name: "華航", type: "tse" },
  { id: "2646", name: "星宇航空", type: "tse" }, { id: "6757", name: "台灣虎航", type: "tse" }
];

// ==========================================
// 2. DOM 元素選取 (DOM Elements)
// ==========================================
const tickerContainer = document.getElementById('etfTicker');
const updateTimeEl = document.getElementById('update-time');
const etfData = {}; 

// Modal 相關
const costModal = document.getElementById('costCalculatorModal');
const costModalCloseBtn = document.getElementById('modalCloseBtn');
const costModalTitle = document.getElementById('modalTitle');
const modalAmountInput = document.getElementById('modalAmount');
const modalDiscountInput = document.getElementById('modalDiscount');
const modalMgmtFeeInput = document.getElementById('modalMgmtFee'); 
const buyCostResultEl = document.getElementById('buyCostResult');
const sellCostResultEl = document.getElementById('sellCostResult');
const mgmtFeeResultEl = document.getElementById('mgmtFeeResult');
const totalCostResultEl = document.getElementById('totalCostResult');

// ETF 持股分析 Modal
const etfDetailModal = document.getElementById('etfDetailModal');

// 介面與登入元素
const mascot = document.getElementById('mascot-helper');
const bubble = document.querySelector('.chat-bubble');
const tickerWrap = document.querySelector('.ticker-wrap');
const loginOverlay = document.getElementById('login-overlay');
const usernameInput = document.getElementById('usernameInput');
const passwordInput = document.getElementById('passwordInput');
const submitLoginBtn = document.getElementById('submitLoginBtn');
const loginMsg = document.getElementById('loginMsg');
const logoutBtn = document.getElementById('logoutBtn');
const userInfoSpan = document.getElementById('userInfo');
const togglePasswordBtn = document.getElementById('togglePasswordBtn');
const registerLink = document.getElementById('registerLink');
const forgotLink = document.getElementById('forgotLink');
const backToLoginLink = document.getElementById('backToLoginLink');

// 搜尋相關
const searchInput = document.getElementById("searchInput");
const searchBtn = document.getElementById("searchBtn");
const statusDiv = document.getElementById("dbStatus"); 
const resultDiv = document.getElementById("searchResultArea"); 

let currentMode = 'login'; 

// ==========================================
// 3. 核心 API 與工具函式 (Core Logic)
// ==========================================

function buildUrl(rawUrl) {
  if (!PROXY_URL) return rawUrl;
  return `${PROXY_URL.replace(/\/$/, '')}/fetch?url=${encodeURIComponent(rawUrl)}`;
}

function getQueryString(stocks) {
  return stocks.map(stock => `${stock.type}_${stock.id}.tw`).join('|');
}

// ✨ 新增：向後端同步收藏資料的函式
// script.js
async function syncFavorites() {
    try {
        // ✨ 注意：路徑必須是 /api/favorites，且需帶上 credentials 以傳送 session
        const res = await fetch(`${PROXY_URL}/api/favorites`, { 
            method: 'GET',
            credentials: 'include' 
        });
        const data = await res.json();
        
        if (data.success) {
            userFavorites = data.favorites || [];
            console.log("收藏同步成功：", userFavorites);
        }
    } catch (err) {
        console.error("同步收藏資料失敗:", err);
    }
}

// ==========================================
// 4. ETF Ticker 與 圖表 (Top Section)
// ==========================================

function initializeUICards() {
  if (!tickerContainer) return;
  tickerContainer.innerHTML = ''; 
  Object.entries(etfs).forEach(([symbol, data]) => {
    const card = document.createElement('div');
    card.className = 'etf-card';
    card.id = `card-${symbol}`;
    card.style.cursor = 'pointer';

    card.innerHTML = `
      <button class="cost-calculator-btn" data-symbol="${symbol}" title="交易成本試算"><i class="fas fa-calculator"></i></button>
      <div class="etf-name">${escapeStockText(data.name)}</div>
      <div class="etf-symbol">${symbol}</div>
      <div class="etf-price" id="price-${symbol}">讀取中...</div>
      <div class="etf-change" id="change-${symbol}"></div>
      <div class="etf-meta" id="meta-${symbol}"></div>
      <canvas id="chart-${symbol}" width="300" height="44"></canvas>
    `;
    tickerContainer.appendChild(card);
    etfData[symbol] = { prices: [] };
  });
}

let marketSnapshotRequest = null;
function fetchMarketSnapshot() {
    if (marketSnapshotRequest) return marketSnapshotRequest;
    marketSnapshotRequest = (async () => {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 8000);
        try {
            const response = await fetch(`${PROXY_URL}/api/snapshot`, { credentials: 'include', signal: controller.signal });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const snapshot = await response.json();
            if (!Array.isArray(snapshot.etf)) throw new Error('快照格式錯誤');
            return snapshot;
        } finally { clearTimeout(timeout); }
    })().finally(() => { marketSnapshotRequest = null; });
    return marketSnapshotRequest;
}

function showEtfFailure(reason) {
    if (!tickerContainer) return;
    Object.keys(etfs).forEach(sym => {
        const priceEl = document.getElementById(`price-${sym}`);
        const changeEl = document.getElementById(`change-${sym}`);
        const metaEl = document.getElementById(`meta-${sym}`);
        if (!priceEl || !metaEl) return;
        if (priceEl.dataset.hasQuote !== 'true') {
            priceEl.textContent = '0.00';
            if (changeEl) changeEl.textContent = '暫無報價';
        }
        metaEl.textContent = `${reason}；稍後自動重試`;
    });
    if (updateTimeEl) updateTimeEl.textContent = '更新失敗，稍後自動重試';
}

function renderEtfSnapshot(snapshot) {
    if (!tickerContainer) return;
    if (updateTimeEl) updateTimeEl.textContent = snapshot.updatedAt ? `最後更新：${snapshot.updatedAt}` : '尚無來源更新時間';
    const etfMap = new Map(snapshot.etf.map(item => [item.symbol, item]));
    Object.keys(etfs).forEach(sym => {
        const priceEl = document.getElementById(`price-${sym}`);
        const changeEl = document.getElementById(`change-${sym}`);
        const metaEl = document.getElementById(`meta-${sym}`);
        if (!priceEl || !changeEl || !metaEl) return;
        const item = etfMap.get(sym);
        const price = Number(item?.price);
        if (!item || !Number.isFinite(price) || price <= 0) {
            priceEl.textContent = '0.00';
            priceEl.dataset.hasQuote = 'false';
            changeEl.textContent = '暫無報價';
            metaEl.textContent = item ? '來源尚無有效報價，稍後自動更新' : '快照缺少此 ETF，稍後自動更新';
            console.warn(`ETF ${sym}：${metaEl.textContent}`);
            return;
        }
        const change = Number(item.change) || 0;
        const pct = Number(item.changePct) || 0;
        priceEl.textContent = price.toFixed(2);
        priceEl.dataset.hasQuote = 'true';
        const sign = change > 0 ? '+' : '';
        changeEl.textContent = `${sign}${change.toFixed(2)} (${pct.toFixed(2)}%)`;
        changeEl.style.color = change > 0 ? '#0a8b48' : (change < 0 ? '#c0392b' : '#666');
        metaEl.textContent = snapshot.updatedAt ? `時間：${snapshot.updatedAt}` : '尚無來源更新時間';
        const data = etfData[sym];
        if (data) {
            data.prices.push(price);
            if (data.prices.length > MAX_POINTS) data.prices.shift();
            drawChart(sym, data.prices, change);
        }
    });
}

async function fetchQuotes() {
    if (!tickerContainer) return;
    try { renderEtfSnapshot(await fetchMarketSnapshot()); }
    catch (error) {
        console.error('ETF 報價更新失敗', error);
        showEtfFailure(error.name === 'AbortError' ? '讀取逾時' : '無法讀取報價');
    }
}

function renderSectorTableFromSnapshot(stocks, tableId, sectorData = []) {
    const tbody = document.getElementById(tableId);
    if (!tbody) return;

    const sectorMap = new Map((sectorData || []).map(item => [item.id, item]));
    tbody.innerHTML = '';

    stocks.forEach(stock => {
        const stockData = sectorMap.get(stock.id);
        if (!stockData) return;

        const currentPrice = parseFloat(stockData.price || 0);
        const diff = parseFloat(stockData.change || 0);
        const diffPercent = parseFloat(stockData.changePct || 0).toFixed(2);
        const volume = stockData.volume || '0';
        const colorClass = diff > 0 ? 'stock-up' : (diff < 0 ? 'stock-down' : 'stock-flat');
        const arrow = diff > 0 ? '▲' : (diff < 0 ? '▼' : '');

        const isFav = userFavorites.includes(stock.id);
        const starClass = isFav ? 'fas fa-star' : 'far fa-star';
        const starColor = isFav ? '#ffcc00' : '#7b80a1';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><span style="color: #888;">${stock.id}</span></td>
            <td><b>${escapeStockText(stockData.name || stock.name)}</b></td>
            <td class="${colorClass}">${currentPrice.toFixed(2)}</td>
            <td class="${colorClass}">${arrow} ${Math.abs(diff).toFixed(2)}</td>
            <td class="${colorClass}">${diffPercent}%</td>
            <td>${volume}</td>
            <td>
                <button class="fav-btn"
                                onclick="toggleFavorite(event, '${stock.id}')"
                                style="background:none; border:none; cursor:pointer; color:${starColor}; font-size:1.1rem;">
                    <i class="${starClass}"></i>
                </button>
            </td>`;
        tbody.appendChild(tr);
    });

    if (!tbody.children.length) {
        const colspan = tableId === 'food-stock-list' ? 7 : 6;
        tbody.innerHTML = `<tr><td colspan="${colspan}">暫無資料</td></tr>`;
    }
}

function updateSectorHeatmapFromSnapshot(sectorsData = {}) {
    const sectors = [
        { key: 'food', elId: 'heat-food', barId: 'bar-food' },
        { key: 'travel', elId: 'heat-travel', barId: 'bar-travel' },
        { key: 'aviation', elId: 'heat-aviation', barId: 'bar-aviation' }
    ];

    sectors.forEach(({ key, elId, barId }) => {
        const list = sectorsData[key] || [];
        const avg = list.length
            ? list.reduce((sum, item) => sum + (parseFloat(item.changePct || 0) || 0), 0) / list.length
            : 0;

        const barEl = document.getElementById(barId);
        const cardEl = document.getElementById(elId);
        if (!barEl || !cardEl) return;

        const valEl = cardEl.querySelector('.heat-value');
        const statEl = cardEl.querySelector('.heat-status');
        const color = avg > 0.001 ? '#ff4444' : (avg < -0.001 ? '#00ff9d' : '#ffffff');

        if (valEl) {
            valEl.innerText = `${avg.toFixed(2)} %`;
            valEl.style.color = color;
        }

        if (statEl) {
            let mood = '市場觀望 ☁️';
            if (avg > 1.0) mood = '極度熱絡 🔥';
            else if (avg > 0.1) mood = '買氣積極 🚀';
            else if (avg < -1.0) mood = '恐慌拋售 ❄️';
            else if (avg < -0.1) mood = '情緒保守 📉';
            statEl.innerText = mood;
        }

        let widthPct = 50 + (avg * 10);
        widthPct = Math.max(10, Math.min(90, widthPct));
        barEl.style.width = `${widthPct}%`;
        barEl.style.backgroundColor = color;
        barEl.style.boxShadow = `0 0 10px ${color}`;
    });
}

function updateUICards(results) {
  results.forEach(r => {
    const sym = `${r.c}.TW`;
    if (etfs[sym] === undefined) return;
    const priceEl = document.getElementById(`price-${sym}`);
    const changeEl = document.getElementById(`change-${sym}`);
    const metaEl = document.getElementById(`meta-${sym}`);
    if(!priceEl) return;

    const high = parseFloat(r.h); const low = parseFloat(r.l);
    let finalPriceStr = r.z; 

    if (finalPriceStr === '-') {
      let candidate = null;
      if (r.b && r.b !== '-') candidate = r.b.split('_')[0];
      if (!candidate && r.a && r.a !== '-') candidate = r.a.split('_')[0];
      if (candidate) {
        let val = parseFloat(candidate);
        if (!isNaN(high) && !isNaN(low)) {
          if (val < low) val = (Math.abs(val-high) < Math.abs(val-low)) ? high : low;
          if (val < low) val = high; 
        }
        finalPriceStr = val.toString();
      }
    }

    if (finalPriceStr === '-' || !finalPriceStr) {
      priceEl.textContent = r.y; changeEl.textContent = '試撮中'; changeEl.style.color = '#666';
      metaEl.textContent = `昨收：${r.y}`; return; 
    }
    const price = parseFloat(finalPriceStr);
    const y = parseFloat(r.y);
    const change = price - y;
    const pct = (change / y) * 100;
    
    priceEl.textContent = price.toFixed(2);
    const sign = change > 0 ? '+' : '';
    changeEl.textContent = `${sign}${change.toFixed(2)} (${pct.toFixed(2)}%)`;
    changeEl.style.color = change > 0 ? '#0a8b48' : (change < 0 ? '#c0392b' : '#333');
    
    let metaText = `時間：${r.t}`;
    if (r.v) metaText += ` 量：${r.v}`;
    metaEl.textContent = metaText;

    const data = etfData[sym];
    data.prices.push(price);
    if (data.prices.length > MAX_POINTS) data.prices.shift();
    drawChart(sym, data.prices, change);
  });
  if(updateTimeEl) updateTimeEl.textContent = `最後更新：${new Date().toLocaleTimeString()}`;
}

function drawChart(symbol, prices, changeHint) {
  const canvas = document.getElementById(`chart-${symbol}`);
  if (!canvas || prices.length < 2) return;
  const ctx = canvas.getContext('2d');
  const w = canvas.width; const h = canvas.height;
  ctx.clearRect(0, 0, w, h);
  const min = Math.min(...prices); const max = Math.max(...prices);
  const range = (max - min) || 0.01; 
  const stroke = changeHint > 0 ? '#0a8b48' : (changeHint < 0 ? '#c0392b' : '#666');
  ctx.beginPath(); ctx.lineWidth = 2.0; ctx.strokeStyle = stroke;
  prices.forEach((p, i) => {
    const x = (i / (MAX_POINTS - 1)) * w; 
    const y = h - (((p - min) / range) * (h * 0.8) + (h * 0.1));
    if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
  });
  ctx.stroke();
}

// ==========================================
// 5. 事件監聽 (Event Listeners)
// ==========================================

if(tickerContainer) {
    tickerContainer.addEventListener('click', e => {
        const calcBtn = e.target.closest('.cost-calculator-btn');
        if(calcBtn) { 
            e.stopPropagation(); 
            costModalTitle.textContent=`試算: ${etfs[calcBtn.dataset.symbol].name}`; 
            calculateCosts(); 
            costModal.style.display='flex'; 
            return;
        }

        const card = e.target.closest('.etf-card');
        if (card) {
            const symbol = card.id.replace('card-', '');
            openEtfAnalysis(symbol);
        }
    });
}

if(costModalCloseBtn) {
    costModalCloseBtn.addEventListener('click', () => costModal.style.display='none');
}

window.addEventListener('click', (e) => {
    if (e.target === etfDetailModal) etfDetailModal.style.display = 'none';
    if (e.target === costModal) costModal.style.display = 'none';
});

// ⭐ 獨立的收藏處理函式 (直接綁定在 HTML 上)
async function toggleFavorite(event, stockId) {
    event.stopPropagation();
    event.preventDefault();

    const favBtn = event.currentTarget;
    const icon = favBtn.querySelector('i');
    const isAdding = icon.classList.contains('far'); 

    // ✨ 1. 立即更新前端變數與 UI (不等待伺服器)
    if (isAdding) {
        icon.classList.replace('far', 'fas'); 
        favBtn.style.color = '#ffcc00';         
        if (!userFavorites.includes(stockId)) {
            userFavorites.push(stockId);
        }
    } else {
        icon.classList.replace('fas', 'far'); 
        favBtn.style.color = '#7b80a1';
        userFavorites = userFavorites.filter(id => id !== stockId);
    }

    // ✨ 2. 如果是在「我的收藏」頁面，點擊取消後立即隱藏該行
    if (!isAdding && window.location.pathname.includes('favorite-list.php')) {
        const row = favBtn.closest('tr');
        if (row) row.style.opacity = '0';
        if (row) row.remove(); // 立即移除
    }

    try {
        // ✨ 3. 背景默默發送請求
        const token = await getCsrfToken();
        const response = await fetch(`${PROXY_URL}/api/favorites`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
            credentials: 'include', 
            body: JSON.stringify({ stockId, action: isAdding ? 'add' : 'remove' })
        });

        const result = await response.json();
        if (!result.success) throw new Error(result.message);
        
    } catch (err) {
        // ✨ 4. 只有失敗時才回滾 UI 並彈出提醒
        console.error("同步失敗:", err);
        alert("連線不穩定，收藏狀態未同步");
        
        icon.classList.toggle('far');
        icon.classList.toggle('fas');
        favBtn.style.color = isAdding ? '#7b80a1' : '#ffcc00';
        
        if (isAdding) userFavorites = userFavorites.filter(id => id !== stockId);
        else userFavorites.push(stockId);
    }
}

// ==========================================
// 6. Modal 邏輯 (Cost Calculator & ETF Analysis)
// ==========================================

function calculateCosts() {
  const amount = parseFloat(modalAmountInput.value) || 0;
  const discount = parseFloat(modalDiscountInput.value) || 10; 
  const mgmtFeePct = parseFloat(modalMgmtFeeInput.value) || 0;
  const commission = Math.max(20, Math.round(amount * 0.001425 * (discount / 10)));
  const tax = Math.round(amount * 0.001);
  const mgmtFee = Math.round(amount * mgmtFeePct / 100);
  
  if(buyCostResultEl) buyCostResultEl.textContent = commission.toLocaleString();
  if(sellCostResultEl) sellCostResultEl.textContent = (commission + tax).toLocaleString();
  if(mgmtFeeResultEl) mgmtFeeResultEl.textContent = mgmtFee.toLocaleString();
  if(totalCostResultEl) totalCostResultEl.textContent = (commission * 2 + tax).toLocaleString();
}

[modalAmountInput, modalDiscountInput, modalMgmtFeeInput].forEach(input => {
    if(input) input.addEventListener('input', calculateCosts);
});

// ★★★ 開啟 ETF 持股分析視窗 (顯示換股日 - JS版本) ★★★
function openEtfAnalysis(symbol) {
    const detailModal = document.getElementById('etfDetailModal');
    const title = document.getElementById('etfModalTitle');
    const tbody = document.getElementById('etfHoldingList');
    const emptyMsg = document.getElementById('etfEmptyMsg');
    
    if (!detailModal) {
        console.error("錯誤：找不到 id='etfDetailModal' 的元素");
        return;
    }

    const etfName = etfs[symbol] ? etfs[symbol].name : symbol;
    if(title) title.textContent = `${etfName} (${symbol})`;
    
    if(tbody) {
        tbody.innerHTML = '';
        tbody.parentElement.style.display = 'none';
        if (emptyMsg) emptyMsg.textContent = '目前沒有可驗證的 ETF 成分股資料。';
        if (emptyMsg) emptyMsg.style.display = 'block';
    }
    detailModal.style.display = 'flex';
}

// ==========================================
// 7. 個股表格更新 (Table Data) - 下方區域
// ==========================================

async function updateTableData(stocks, tableId) {
  const tbody = document.getElementById(tableId);
  if (!tbody) return;

    // 先用後端 snapshot，避免第三方 proxy 失效導致畫面空白
    try {
        const snapRes = await fetch(`${PROXY_URL}/api/snapshot`, { credentials: 'include' });
        if (snapRes.ok) {
            const snapshot = await snapRes.json();
            const allSnapshotRows = [
                ...(snapshot?.sectors?.food || []),
                ...(snapshot?.sectors?.travel || []),
                ...(snapshot?.sectors?.aviation || [])
            ];
            const rowMap = new Map(allSnapshotRows.map(r => [r.id, r]));

            tbody.innerHTML = '';
            stocks.forEach(stock => {
                const stockData = rowMap.get(stock.id);
                if (!stockData) return;

                const currentPrice = parseFloat(stockData.price || 0);
                const diff = parseFloat(stockData.change || 0);
                const diffPercent = parseFloat(stockData.changePct || 0).toFixed(2);
                const volume = stockData.volume || '0';
                const colorClass = diff > 0 ? 'stock-up' : (diff < 0 ? 'stock-down' : 'stock-flat');
                const arrow = diff > 0 ? '▲' : (diff < 0 ? '▼' : '');

                const tr = document.createElement('tr');
                const isFav = userFavorites.includes(stock.id);
                const starClass = isFav ? 'fas fa-star' : 'far fa-star';
                const starColor = isFav ? '#ffcc00' : '#7b80a1';

                tr.innerHTML = `
                    <td><span style="color: #888;">${stock.id}</span></td>
                    <td><b>${escapeStockText(stockData.name || stock.name)}</b></td>
                    <td class="${colorClass}">${currentPrice.toFixed(2)}</td>
                    <td class="${colorClass}">${arrow} ${Math.abs(diff).toFixed(2)}</td>
                    <td class="${colorClass}">${diffPercent}%</td>
                    <td>${volume}</td>
                    <td>
                        <button class="fav-btn"
                                        onclick="toggleFavorite(event, '${stock.id}')"
                                        style="background:none; border:none; cursor:pointer; color:${starColor}; font-size:1.1rem;">
                            <i class="${starClass}"></i>
                        </button>
                    </td>`;

                tr.style.cursor = 'pointer';
                tr.onclick = (event) => {
                    if (event.target.closest('.fav-btn')) return;
                    openReverseSearch(stockData.name || stock.name, stock.id);
                };
                tbody.appendChild(tr);
            });

            if (!tbody.children.length) {
                const colspan = tableId === 'food-stock-list' || tableId === 'favorite-stock-list' ? 7 : 6;
                tbody.innerHTML = `<tr><td colspan="${colspan}">暫無資料</td></tr>`;
            }
            return;
        }
    } catch (e) {
        console.warn('行情快照讀取失敗', e);
        const colspan = tableId === 'food-stock-list' || tableId === 'favorite-stock-list' ? 7 : 6;
        tbody.innerHTML = `<tr><td colspan="${colspan}">行情服務暫時無法使用</td></tr>`;
    }
}

async function updateAllTables() {
    if (loginOverlay && !loginOverlay.classList.contains('hidden')) return;

    try {
        const snapshot = await fetchMarketSnapshot();
        renderEtfSnapshot(snapshot);

        const updateText = snapshot.updatedAt || new Date().toLocaleTimeString();
        document.querySelectorAll('.update-time').forEach(el => el.innerText = updateText);

        renderSectorTableFromSnapshot(foodStocks, 'food-stock-list', snapshot?.sectors?.food || []);
        renderSectorTableFromSnapshot(travelStocks, 'travel-stock-list', snapshot?.sectors?.travel || []);
        renderSectorTableFromSnapshot(aviationStocks, 'aviation-stock-list', snapshot?.sectors?.aviation || []);
        updateSectorHeatmapFromSnapshot(snapshot?.sectors || {});
    } catch (error) {
        console.error("Heatmap/Table Update Error:", error);
        showEtfFailure(error.name === 'AbortError' ? '讀取逾時' : '無法讀取報價');
    }

}

// ==========================================
// 8. 新聞跑馬燈 (News Ticker)
// ==========================================
function initTicker() {
    return window.initializeSiteTicker?.();
}

// ==========================================
// 9. 旅行社搜尋 (Agency Search)
// ==========================================
let agencyData = [];
function initAgencySearch() {
    fetch('data/tqaa_data.json').then(r => r.json()).then(data => {
        agencyData = data;
        if(statusDiv) {
            statusDiv.innerHTML = `✓ 資料庫已連線 (共 ${agencyData.length} 筆)`;
            statusDiv.style.color = "#00ff9d";
        }
    }).catch(e => { if(statusDiv) statusDiv.innerHTML = "❌ 資料庫讀取失敗"; });

    if (searchBtn) searchBtn.addEventListener('click', performSearch);
    if (searchInput) searchInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') performSearch(); });
}

function performSearch() {
    const keyword = searchInput.value.trim();
    if(resultDiv) { resultDiv.style.display = "none"; resultDiv.innerHTML = ""; }
    if (!keyword) { alert("請輸入名稱"); return; }
    
    const results = agencyData.filter(item => item.name.includes(keyword));
    if(resultDiv) resultDiv.style.display = "block";
    
    if (results.length > 0) {
        let html = "";
        results.forEach(item => {
            html += `<div class="cyber-card">
                      <div class="cyber-card-name">⭕ ${item.name}</div>
                      <div class="cyber-card-info">編號：${item.code} <br>電話：${item.tel} <br>地址：${item.address}</div>
                    </div>`;
        });
        resultDiv.innerHTML = html;
    } else { 
        resultDiv.innerHTML = `<div style="color:#ff4444;padding:10px;">❌ 找不到</div>`; 
    }
}

// ==========================================
// 10. 吉祥物與氣泡 (Mascot)
// ==========================================
// 漫畫選單統一由 gugu.js 處理，避免重複切換開關。

// ==========================================
// 11. 登入系統邏輯 (Login System)
// ==========================================
function switchMode(mode) {
    if(!loginOverlay) return; 
    currentMode = mode;
    const policyHint=document.getElementById('registerPolicy');
    if(policyHint)policyHint.style.display=mode==='register'?'block':'none';
    const emailGroup=document.getElementById('registerEmailGroup');
    if(emailGroup)emailGroup.style.display=mode==='register'?'block':'none';
    const title = document.querySelector('.login-title');
    if(title) {
        if (loginMsg) loginMsg.textContent = '';
        if (registerLink) registerLink.style.display = mode === 'login' ? '' : 'none';
        if (forgotLink) forgotLink.style.display = mode === 'login' ? '' : 'none';
        if (backToLoginLink) backToLoginLink.style.display = mode === 'login' ? 'none' : '';
        if (mode === 'login') {
            title.textContent = 'LOGIN';
            submitLoginBtn.textContent = '登 入';
            passwordInput.parentElement.style.display = 'block';
            passwordInput.placeholder = 'Password';
        } else if (mode === 'register') {
            title.textContent = '註冊帳號';
            submitLoginBtn.textContent = '確認註冊';
            passwordInput.parentElement.style.display = 'block';
            passwordInput.placeholder = '請設定密碼';
        } else if (mode === 'forgot') {
            title.textContent = '找回密碼';
            submitLoginBtn.textContent = '寄送重設連結';
            passwordInput.parentElement.style.display = 'none';
        }
    }
}

// 綁定事件
if (registerLink) registerLink.addEventListener('click', (e) => { e.preventDefault(); switchMode('register'); });
if (forgotLink) forgotLink.addEventListener('click', (e) => { e.preventDefault(); switchMode('forgot'); });
if (backToLoginLink) backToLoginLink.addEventListener('click', (e) => { e.preventDefault(); switchMode('login'); });
if (togglePasswordBtn) togglePasswordBtn.addEventListener('click', () => {
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    togglePasswordBtn.classList.toggle('fa-eye');
    togglePasswordBtn.classList.toggle('fa-eye-slash');
});

async function handleSubmit() {
    if(!usernameInput || !passwordInput) return;
    const username = usernameInput.value.trim();
    const password = passwordInput.value;
    if (!username) { if(currentMode === 'register') alert('請輸入帳號'); else loginMsg.textContent = '請輸入帳號'; return; }

    let apiUrl = `${PROXY_URL}/api/login`;
    let bodyData = { username, password };

    if (currentMode === 'register') {
        const policyError = window.accountPolicy.usernameError(username) || window.accountPolicy.passwordError(password, username);
        if (policyError) { loginMsg.textContent = ''; alert(policyError); return; }
        apiUrl = `${PROXY_URL}/api/register`;
        const email=document.getElementById('registerEmail')?.value.trim() || '';
        if(!email || !document.getElementById('registerEmail').checkValidity()){alert('請輸入有效信箱');return;}
        bodyData.email=email;
    }
    else if (currentMode === 'forgot') {
        apiUrl = `${PROXY_URL}/api/forgot`;
        bodyData = { username };
    }

    try {
        submitLoginBtn.disabled=true;
        submitLoginBtn.textContent = '處理中...';
        const res = await fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(bodyData)
        });
        const data = await res.json();

        if (data.success) {
            if (currentMode === 'login') {
                if(loginMsg) loginMsg.textContent = '';
                handleLoginSuccess({ name: username });
            } else {
                alert(data.message);
                switchMode('login');
            }
        } else {
            if(currentMode === 'register') { alert(data.message); }
            else if(loginMsg) loginMsg.textContent = data.message;
        }
    } catch (err) {
        console.error('帳號 API 連線失敗:', err);
        if (loginMsg) loginMsg.textContent = '無法連線到帳號服務，請確認後端已啟動。';
    } finally {
        submitLoginBtn.disabled=false;
        if (currentMode === 'login' && submitLoginBtn) submitLoginBtn.textContent = '登 入';
        else if (currentMode === 'register' && submitLoginBtn) submitLoginBtn.textContent = '確認註冊';
        else if(submitLoginBtn) submitLoginBtn.textContent = '寄送重設連結';
    }
}

// 登入後直接進入主頁；主頁自行載入收藏與報價，避免背景請求卡住登入。
function handleLoginSuccess(user) {
    try { localStorage.setItem('currentUser', user?.name || user?.username || ''); }
    catch (error) { console.warn('無法儲存顯示名稱', error); }
    csrfToken = null;
    window.location.replace(new URL('main.php', SITE_ROOT_URL).href);
}

// 主畫面或返回快取恢復時，不能沿用舊的隱藏、停用狀態。
function restoreLoginForm() {
    if (!loginOverlay) return;
    loginOverlay.classList.remove('hidden');
    const card = loginOverlay.querySelector('.login-card');
    if (card) card.style.display = 'block';
    if (submitLoginBtn) submitLoginBtn.disabled = false;
    switchMode(currentMode);
}
window.addEventListener('pageshow', event => {
    if (loginOverlay && event.persisted) {
        restoreLoginForm();
        void checkLoginStatus();
    }
});
window.addEventListener('pagehide', restoreLoginForm);

async function checkLoginStatus() {
    const showLoggedInUser = (name) => {
        if (userInfoSpan) {
            userInfoSpan.textContent = `Hi, ${name || '使用者'}`;
            userInfoSpan.style.display = 'inline-block';
        }
        if (logoutBtn) logoutBtn.style.display = 'inline-block';
        if (tickerWrap) tickerWrap.style.display = 'flex';
    };

    try {
        const res = await fetch(`${PROXY_URL}/api/status`, { credentials: 'include' });
        if (!res.ok) throw new Error(`登入狀態 API 回應 ${res.status}`);
        const data = await res.json();

        if (data.isLoggedIn) {
            if (loginOverlay) {
                handleLoginSuccess(data.user);
            } else {
                showLoggedInUser(data?.user?.name || data?.user?.username);
            }
            return true;
        }

        if (loginOverlay) {
            loginOverlay.classList.remove('hidden');
            if (tickerWrap) tickerWrap.style.display = 'none';
            const card = document.querySelector('.login-card');
            if (card) {
                card.style.display = 'block';
                card.style.animation = 'fadeIn 0.5s ease-out';
            }
        } else {
            window.location.href = new URL('login.php', SITE_ROOT_URL).href;
        }
    } catch (err) {
        if (loginOverlay) {
            loginOverlay.classList.remove('hidden');
            const card = document.querySelector('.login-card');
            if (card) card.style.display = 'block';
        } else {
            window.location.href = new URL('login.php', SITE_ROOT_URL).href;
        }
    }
    return false;
}

async function getCsrfToken() {
    if (csrfToken) return csrfToken;
    const response = await fetch(`${PROXY_URL}/api/csrf`, { credentials: 'include' });
    const data = await response.json();
    if (!response.ok || !data.success || !data.csrfToken) {
        throw new Error(data.message || '無法取得安全驗證');
    }
    csrfToken = data.csrfToken;
    return csrfToken;
}

async function runFetchLoop() {
  await fetchQuotes();
  // 定期更新由 main-page.js 呼叫 updateAllTables，共用同一份快照。
}

if (submitLoginBtn) submitLoginBtn.addEventListener('click', handleSubmit);
if (passwordInput) passwordInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') handleSubmit(); });

// ==========================================
// 12. 程式啟動 (Entry Point)
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    // 僅在登入頁執行登入狀態檢查，避免 main 頁重複初始化造成閃動/跳轉。
    if (loginOverlay) {
        checkLoginStatus();
    }
});

function openReverseSearch(stockName, stockCode) {
    const modal = document.getElementById('stockReverseSearchModal');
    const titleName = document.getElementById('targetStockName');
    const titleCode = document.getElementById('targetStockCode');
    const listBody = document.getElementById('reverseSearchList');
    const emptyMsg = document.getElementById('reverseSearchEmptyMsg');

    titleName.innerText = stockName;
    titleCode.innerText = stockCode;
    listBody.innerHTML = '';

    emptyMsg.textContent = '目前沒有可驗證的 ETF 成分股資料。';
    emptyMsg.style.display = 'block';
    modal.style.display = 'flex';
}

function updateSectorHeatmap(msgArray) {
    const sectors = [
        { id: 'food', list: foodStocks, elId: 'heat-food', barId: 'bar-food' },
        { id: 'travel', list: travelStocks, elId: 'heat-travel', barId: 'bar-travel' },
        { id: 'aviation', list: aviationStocks, elId: 'heat-aviation', barId: 'bar-aviation' }
    ];

    sectors.forEach(sector => {
        let totalPct = 0;
        let count = 0;

        sector.list.forEach(stock => {
            const d = msgArray.find(m => m.c === stock.id);
            if (d) {
                let pStr = d.z;
                if (pStr === '-' || !pStr || parseFloat(pStr) === 0) {
                    pStr = (d.b && d.b !== '-') ? d.b.split('_')[0] : d.y;
                }
                const p = parseFloat(pStr);
                const y = parseFloat(d.y);
                totalPct += ((p - y) / y) * 100;
                count++;
            }
        });

        const avg = count > 0 ? (totalPct / count) : 0;
        const barEl = document.getElementById(sector.barId);
        const cardEl = document.getElementById(sector.elId);

        if (cardEl && barEl) {
            const valEl = cardEl.querySelector('.heat-value');
            const statEl = cardEl.querySelector('.heat-status');
            
            const color = avg > 0.001 ? '#ff4444' : (avg < -0.001 ? '#00ff9d' : '#ffffff');

            valEl.innerText = `${avg.toFixed(2)} %`;
            valEl.style.color = color;

            let mood = "市場觀望 ☁️";
            if (avg > 1.0) mood = "極度熱絡 🔥";
            else if (avg > 0.1) mood = "買氣積極 🚀";
            else if (avg < -1.0) mood = "恐慌拋售 ❄️";
            else if (avg < -0.1) mood = "情緒保守 📉";
            statEl.innerText = mood;

            let widthPct = 50 + (avg * 10); 
            widthPct = Math.max(10, Math.min(90, widthPct)); 

            barEl.style.width = `${widthPct}%`;
            barEl.style.backgroundColor = color;
            barEl.style.boxShadow = `0 0 10px ${color}`;
        }
    });
}



async function renderFavoritesPage() {
    const tbody = document.getElementById('favorite-stock-list');
    
    // 1. 取得使用者的收藏清單
    await syncFavorites(); 
    
    // 2. 整合所有股票資料來源
    const allStocks = [...foodStocks, ...travelStocks, ...aviationStocks];
    
    // 3. 過濾出已收藏的項目
    const myFavorites = allStocks.filter(s => userFavorites.includes(s.id));
    
    // 4. 調用你現有的 updateTableData 函式來抓取即時股價
    if (myFavorites.length > 0) {
        updateTableData(myFavorites, 'favorite-stock-list');
    } else {
        tbody.innerHTML = '<tr><td colspan="7">尚無收藏股票</td></tr>';
    }
    
}
/* =========================================
   ⭐ 購買力計算機核心邏輯
   ========================================= */

/* =========================================
   ⭐ GUGU 購買力計算機核心邏輯
   ========================================= */

/**
 * 自動從網頁表格中抓取對應代碼的股價
 */
function autoFetchPrice() {
    const symbol = document.getElementById('calcStockSymbol').value.trim();
    const priceInput = document.getElementById('calcStockPrice');
    const nameDisplay = document.getElementById('stockNameDisplay');

    if (!symbol) {
        priceInput.value = '';
        nameDisplay.innerText = '';
        return;
    }

    // 搜尋頁面上所有表格列 (tr)
    const allTableRows = document.querySelectorAll('tr');
    let foundPrice = null;
    let foundName = '';

    allTableRows.forEach(row => {
        // 假設第一欄是代號，第二欄是名稱，第三欄是股價
        const firstCell = row.cells[0];
        if (firstCell && firstCell.innerText.trim() === symbol) {
            foundPrice = row.cells[2] ? row.cells[2].innerText.trim() : null;
            foundName = row.cells[1] ? row.cells[1].innerText.trim() : '';
        }
    });

    if (foundPrice && !isNaN(parseFloat(foundPrice))) {
        priceInput.value = parseFloat(foundPrice);
        nameDisplay.innerText = `✅ 已自動抓取：${foundName}`;
        nameDisplay.style.color = "#00f2ea"; // 電光藍
    } else {
        priceInput.value = '';
        nameDisplay.innerText = '⚠️ 列表中查無此代碼';
        nameDisplay.style.color = "#ff4444"; // 警示紅
    }
}

/**
 * 執行購買力換算
 */
function runCalculator() {
    const price = parseFloat(document.getElementById('calcStockPrice').value);
    const sheets = parseFloat(document.getElementById('calcStockAmount').value);
    const itemSelect = document.getElementById('calcCompareItem');
    const itemPrice = parseInt(itemSelect.value);
    const itemName = itemSelect.options[itemSelect.selectedIndex].text;

    const resultBox = document.getElementById('calcResultDisplay');

    // 防呆檢查
    if (isNaN(price) || isNaN(sheets) || price <= 0 || sheets <= 0) {
        alert("請確認已輸入正確的股票代碼與持有張數！");
        return;
    }

    // 計算總市值 (1張 = 1000股)
    const totalValue = price * 1000 * sheets;
    
    // 計算可購買數量
    const quantity = Math.floor(totalValue / itemPrice);
    
    // 顯示結果與評論邏輯
    resultBox.style.display = 'block';
    let comment = "這筆投資如果換成實物，也是很可觀呢！";
    
    if (quantity === 0) comment = "加油！再存一點就能換到了！💪";
    else if (quantity > 100) comment = "太狂了！你可以請全班同學吃還有找！🎉";
    else if (quantity > 10 && itemPrice >= 15000) comment = "哇！這已經是財富自由的第一步了！🚀";

    resultBox.innerHTML = `
        <div style="padding: 15px; border: 1px dashed #00f2ea; border-radius: 8px; background: rgba(0, 242, 234, 0.05);">
            <p style="margin: 0 0 10px 0;">您的股票總價值約 <strong style="color:#fff; font-size:1.2em;">NT$ ${totalValue.toLocaleString()}</strong></p>
            <p style="margin: 0;">相當於可購買 <span class="calc-highlight" style="font-size:1.5rem; color:#00f2ea; font-weight:bold;">${quantity.toLocaleString()}</span> ${itemName.split(' ')[0]}！</p>
            <p style="font-size: 0.9rem; color: #aaa; margin-top: 10px; font-style: italic;">"${comment}"</p>
        </div>
    `;
}


