<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ETF專區</title>
<style>
    /* 引入賽博龐克風格字體 */
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap');

    :root {
        --gugu-surface: #1a1c2a;
        --gugu-border: #3a3d5a;
        --neon-green: #00f2ea;
        --neon-pink: #f000ff;
        --text-main: #e0e0ff;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        background-color: #0d0f1a;
        color: var(--text-main);
        font-family: 'Orbitron', 'Noto Sans TC', sans-serif;
        padding: 20px;
    }

    /* 頂部導覽列 */
    .nav-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
        background: var(--gugu-surface);
        padding: 15px;
        border-radius: 12px;
        border: 1px solid var(--gugu-border);
    }

    .nav-left, .nav-right { display: flex; align-items: center; gap: 15px; }

    .btn-home {
        background: #25283d;
        color: #fff;
        text-decoration: none;
        padding: 10px 18px;
        border-radius: 8px;
        font-size: 14px;
        border: 1px solid var(--gugu-border);
        transition: 0.3s;
    }
    .btn-home:hover { border-color: var(--neon-green); color: var(--neon-green); }

    h1 { text-align: center; margin-bottom: 10px; color: #fff; }
    .updated { text-align: center; color: #7b80a1; margin-bottom: 25px; font-size: 14px; }

    /* 搜尋與管理按鈕 */
    .search-box input {
        background: #0d0f1a;
        border: 1px solid var(--gugu-border);
        color: white;
        padding: 10px 15px;
        border-radius: 20px;
        width: 220px;
        outline: none;
    }
    .search-box input:focus { border-color: var(--neon-green); }

    .btn-admin-toggle {
        background: var(--neon-pink);
        color: white;
        border: none;
        padding: 10px 18px;
        border-radius: 20px;
        font-weight: bold;
        cursor: pointer;
    }

    /* 管理面板 */
    .admin-panel {
        background: var(--gugu-surface);
        border: 1px solid var(--neon-pink);
        border-radius: 12px;
        padding: 20px;
        max-width: 600px;
        margin: 0 auto 30px;
        display: none;
    }
    .form-group input, .form-group select {
        background: #0d0f1a;
        border: 1px solid var(--gugu-border);
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
    }
    .btn-submit-add {
        background: var(--neon-green);
        color: #0d0f1a;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: bold;
        width: 100%;
    }

    /* 分類選單與卡片 */
    .category-menu {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 30px;
        max-width: 1200px;
        margin: 40px auto;
    }

    .menu-btn {
        background: var(--gugu-surface);
        border: 1px solid var(--gugu-border);
        color: white;
        padding: 45px 30px;
        font-size: 24px;
        border-radius: 20px;
        cursor: pointer;
        transition: 0.3s;
    }
    .menu-btn:hover { border-color: var(--neon-green); transform: translateY(-3px); box-shadow: 0 0 15px rgba(0, 242, 234, 0.3); }

    .btn-back {
        background: #25283d;
        border: 1px solid var(--gugu-border);
        color: #adb5bd;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        margin-bottom: 20px;
        transition: 0.3s;
    }
    .btn-back:hover { color: white; border-color: var(--neon-green); }

    .category-title { color: var(--neon-green); border-left: 6px solid var(--neon-green); padding-left: 12px; font-size: 28px; margin-bottom: 20px; }
    .sub-title { color: var(--neon-pink); font-size: 20px; margin: 20px 0 12px; }

    /* ⭐ 修改卡片，加入可點擊的視覺回饋 */
    .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
    .card {
        background: var(--gugu-surface);
        border: 1px solid var(--gugu-border);
        border-radius: 12px;
        padding: 15px;
        cursor: pointer;
        transition: 0.3s;
        position: relative;
    }
    .card:hover { border-color: var(--neon-green); transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0, 242, 234, 0.2); }

    .symbol { font-size: 20px; font-weight: bold; color: var(--neon-green); }
    .btn-delete { background: none; border: none; color: #888; cursor: pointer; font-size: 16px; padding: 5px; z-index: 10; position: relative;}
    .btn-delete:hover { color: #ff5555; }
    .price { font-size: 24px; font-weight: bold; margin-top: 10px; }
    .up { color: #00ff66; }
    .down { color: #ff5555; }

    /* ⭐ 新增：詳細資訊彈窗 Modal 樣式 */
    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(13, 15, 26, 0.85); backdrop-filter: blur(5px);
        display: none; justify-content: center; align-items: center; z-index: 1000;
    }
    .modal-content {
        background: var(--gugu-surface); border: 1px solid var(--neon-green);
        border-radius: 12px; padding: 25px; width: 90%; max-width: 450px;
        box-shadow: 0 0 20px rgba(0, 242, 234, 0.2); position: relative;
    }
    .close-btn {
        position: absolute; top: 15px; right: 20px; font-size: 28px;
        cursor: pointer; color: #888; transition: 0.3s; line-height: 1;
    }
    .close-btn:hover { color: var(--neon-pink); }
    .details-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px;
    }
    .detail-item {
        background: #0d0f1a; padding: 15px; border-radius: 8px; border: 1px solid var(--gugu-border); text-align: center;
    }
    .detail-label { color: #7b80a1; font-size: 13px; margin-bottom: 8px; }
    .detail-value { color: white; font-size: 18px; font-weight: bold; color: var(--neon-green); }
    .cost-panel {
        margin-top: 18px; padding: 15px; border-radius: 8px;
        background: #0d0f1a; border: 1px solid var(--gugu-border);
    }
    .cost-panel-title { color: white; font-size: 16px; margin-bottom: 12px; }
    .cost-input-row { display: flex; gap: 10px; }
    .discipline-inputs {
        display: grid; grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px; margin-bottom: 10px;
    }
    .discipline-field label {
        display: block; color: #7b80a1; font-size: 12px; margin-bottom: 6px;
    }
    .cost-input {
        min-width: 0; flex: 1; background: var(--gugu-surface);
        border: 1px solid var(--gugu-border); color: white;
        padding: 10px 12px; border-radius: 6px; outline: none;
        width: 100%;
    }
    .cost-input:focus { border-color: var(--neon-green); }
    .btn-save-cost {
        border: none; border-radius: 6px; padding: 10px 14px;
        color: #0d0f1a; background: var(--neon-green);
        font-weight: bold; cursor: pointer;
    }
    .cost-result { margin-top: 12px; text-align: center; }
    .cost-result-label { color: #7b80a1; font-size: 13px; }
    .cost-result-value { margin-top: 5px; font-size: 24px; font-weight: bold; }
    .discipline-results {
        display: grid; grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px; margin-top: 12px;
    }
    .discipline-result-card {
        background: var(--gugu-surface); border: 1px solid var(--gugu-border);
        border-radius: 8px; padding: 10px; text-align: center;
    }
    .discipline-result-label { color: #7b80a1; font-size: 12px; margin-bottom: 6px; }
    .discipline-result-value { color: white; font-size: 18px; font-weight: bold; }
    .card-discipline-row {
        margin-top: 6px; display: grid; grid-template-columns: 1fr 1fr;
        gap: 6px; font-size: 12px; color: #7b80a1;
    }
    .card-discipline-row span { color: white; font-weight: bold; }
    .cost-help { color: #7b80a1; font-size: 11px; line-height: 1.6; margin-top: 10px; }
    @media (max-width: 520px) {
        .discipline-inputs, .discipline-results { grid-template-columns: 1fr; }
        .cost-input-row { flex-direction: column; }
    }
</style>
<?php require __DIR__ . '/includes/site-head.php'; ?>
</head>
<body class="site-layout">
<?php require __DIR__ . '/includes/site-header.php'; ?>

<div class="nav-bar">
    <div class="nav-left">
        <a href="main.php" class="btn-home">⬅ 返回主頁</a>
    </div>
    <div class="nav-right">
        <button class="btn-admin-toggle" onclick="toggleAdminPanel()">🛠️ 上市/下架管理</button>
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="搜尋 ETF 代號或名稱..." oninput="handleSearch()">
        </div>
    </div>
</div>

<div id="adminPanel" class="admin-panel">
    <h3>📢 新股上市 / 手動新增 ETF</h3>
    <div class="form-group">
        <input type="text" id="newId" placeholder="ETF代號 (如: 00999)">
        <input type="text" id="newName" placeholder="ETF名稱 (如: 某某高股息)">
    </div>
    <div class="form-group">
        <select id="newCategory" onchange="updateSubCategoryOptions()">
            <option value="">--請選擇大分類--</option>
        </select>
        <input type="text" id="newSubKey" placeholder="子分類標題 (如: 高股息、市值型)">
    </div>
    <button class="btn-submit-add" onclick="submitNewETF()">確認上市並加入列表</button>
</div>

<h1>ETF專區</h1>
<div id="updatedTime" class="updated"></div>

<div id="etf-container">
    <div class="loading">載入中...</div>
</div>

<div id="detailsModal" class="modal-overlay" onclick="closeModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h2 id="modalTitle" style="color: white; margin-bottom: 5px;">--</h2>
        <div id="modalSubtitle" style="color: #7b80a1; font-size: 14px;"></div>
        <div id="modalBody">
            </div>
    </div>
</div>

<script>
let apiCategories = null;
let mergedCategories = null;
let globalQuoteMap = {};
let globalPdMap = {}; 
let allEtfList = [];
let currentView = 'menu';
let activeCategoryKey = null;
let deletedETFs = JSON.parse(localStorage.getItem('realDeletedETFs')) || [];
let addedETFs = JSON.parse(localStorage.getItem('realAddedETFs')) || [];
let etfDisciplineSettings = loadDisciplineSettings();

// 直接讀取同資料夾的 etf_crawler.php，這支就是你剛剛做的 120 檔 ETF 爬蟲
const ETF_CRAWLER_URL = 'etf_crawler.php';

async function loadETF() {
    try {
        const etfRes = await fetch(`${ETF_CRAWLER_URL}?_=${Date.now()}`);
        if(!etfRes.ok) throw new Error('ETF爬蟲API失敗，請確認 etf_crawler.php 是否放在同一個資料夾');
        
        const rawData = await etfRes.json();
        const etfList = normalizeETFResponse(rawData).slice(0, 120);
        allEtfList = etfList;
        if (etfList.length === 0) throw new Error('ETF爬蟲有回傳，但沒有找到 ETF 清單');

        apiCategories = buildCategoriesFromFetchedEtfs(etfList);

        globalQuoteMap = {};
        globalPdMap = {};
        etfList.forEach(etf => {
            globalQuoteMap[etf.id] = etf;
            globalPdMap[etf.id] = etf.premium_discount;
        });
        
        document.getElementById('updatedTime').innerHTML = `更新時間：${new Date().toLocaleString('zh-TW', { hour12: false })}　共 ${etfList.length} 檔 ETF`;
        initCategorySelect();
        mergeData();
        renderData();
    } catch(error) {
        console.error(error);
        document.getElementById('etf-container').innerHTML = `<div class="error" style="color:var(--neon-pink); text-align:center;">ETF資料載入失敗，請確認 etf_crawler.php 是否與 etf.php 放在同一個資料夾。</div>`;
    }
}

function normalizeETFResponse(rawData) {
    let list = [];
    const nameMap = {};

    // 如果 etf_crawler.php 同時有 names 與 data，先把 names 建成對照表
    if (rawData && Array.isArray(rawData.names)) {
        rawData.names.forEach(item => {
            const id = String(item.id ?? item.a ?? '').trim().toUpperCase();
            const name = String(item.name ?? item.b ?? '').trim();
            if (id && name) nameMap[id] = name;
        });
    }

    if (Array.isArray(rawData)) {
        list = rawData;
    } else if (rawData && Array.isArray(rawData.data)) {
        list = rawData.data;
    } else if (rawData && Array.isArray(rawData.names)) {
        list = rawData.names;
    }

    return list
        .filter(item => item && (item.id || item.a))
        .map(item => {
            const id = String(item.id ?? item.a ?? '').trim().toUpperCase();
            const nameFromData = String(item.name ?? item.b ?? '').trim();
            return {
                id: id,
                name: nameFromData || nameMap[id] || id,
                price: toNumberOrNull(item.price ?? item.e ?? null),
                nav: toNumberOrNull(item.nav ?? item.f ?? null),
                premium_discount: toNumberOrNull(item.premium_discount ?? item.g ?? null),
                changePct: toNumberOrNull(item.changePct ?? item.change_pct ?? item.z ?? null)
            };
        })
        .filter(item => item.id !== '');
}

function toNumberOrNull(value) {
    if (value === null || value === undefined) return null;
    const text = String(value).trim().replace(/[,％%]/g, '');
    if (text === '' || text === '-' || text === '--' || text === '未結出') return null;
    const number = Number(text);
    return Number.isFinite(number) ? number : null;
}

function createEmptyCategories() {
    return {
        taiwan: {
            name: '台股 ETF',
            '市值型 / 大盤': [],
            '中小型 / 櫃買': [],
            '高股息 / 收益型': [],
            '月配息 / 季配息': [],
            '科技 / 半導體': [],
            'AI / 電動車 / 5G': [],
            'ESG / 永續 / 低碳': [],
            '金融 / 產業主題': [],
            '低波動 / 因子策略': [],
            '台股其他主題': []
        },
        overseas: {
            name: '海外股票 ETF',
            '美股大盤 / 標普': [],
            '美股科技 / NASDAQ': [],
            '中國 / 香港': [],
            '日本 / 韓國': [],
            '越南 / 東協 / 亞洲': [],
            '印度 / 新興市場': [],
            '全球 / 已開發市場': [],
            '海外高股息 / 收益': [],
            '海外其他主題': []
        },
        bond: {
            name: '債券 ETF',
            '美國公債長天期': [],
            '美國公債中短天期': [],
            '投資級公司債': [],
            '金融債': [],
            '高收益債': [],
            '新興市場債': [],
            '債券其他': []
        },
        commodity: {
            name: '商品 / 期貨 ETF',
            '黃金 / 貴金屬': [],
            '原油 / 能源': [],
            '期貨 / 商品籃子': [],
            '商品其他': []
        },
        leveraged: {
            name: '槓桿 / 反向 ETF',
            '台股槓桿': [],
            '台股反向': [],
            '海外槓桿': [],
            '海外反向': [],
            '商品槓桿 / 反向': [],
            '槓桿反向其他': []
        },
        other: {
            name: '其他 ETF',
            '特殊策略 / 無法判斷': []
        }
    };
}

function buildCategoriesFromFetchedEtfs(etfList) {
    const categories = createEmptyCategories();
    const usedIds = new Set();

    etfList.forEach(etf => {
        const target = classifyETF(etf);
        if (!categories[target.categoryKey]) target.categoryKey = 'other';
        if (!categories[target.categoryKey][target.subKey]) target.subKey = '未分類';
        categories[target.categoryKey][target.subKey].push({ id: etf.id, name: etf.name });
        usedIds.add(etf.id);
    });

    // 保險：如果有任何 ETF 沒被分類到，放入其他 ETF / 未分類
    etfList.forEach(etf => {
        const exists = Object.values(categories).some(category =>
            Object.entries(category).some(([subKey, etfList]) =>
                subKey !== 'name' && Array.isArray(etfList) && etfList.some(item => item.id === etf.id)
            )
        );
        if (!exists) categories.other['未分類'].push({ id: etf.id, name: etf.name });
    });

    Object.keys(categories).forEach(catKey => {
        Object.keys(categories[catKey]).forEach(subKey => {
            if (subKey === 'name') return;
            categories[catKey][subKey].sort((a, b) => a.id.localeCompare(b.id));
        });
    });

    return categories;
}

function classifyETF(etf) {
    const id = String(etf.id || '').toUpperCase();
    const name = String(etf.name || '');
    const text = `${id} ${name}`.toUpperCase();

    const isOverseas = /美國|美股|NASDAQ|那斯達克|標普|S&P|SP500|道瓊|費城|NYSE|FANG|中國|大陸|中證|上證|滬深|香港|恒生|恆生|A股|H股|港股|日本|韓國|越南|亞洲|東協|亞太|印度|新興|巴西|拉美|全球|世界|MSCI|WORLD|GLOBAL|CHINA|HANG SENG|CSI|JAPAN|KOREA|VIETNAM|ASEAN|ASIA|INDIA|EMERGING|BRAZIL/.test(text);
    const isCommodity = /黃金|白銀|貴金屬|原油|石油|能源|期貨|商品|銅|農產品|GOLD|SILVER|OIL|BRENT|WTI|COMMODITY/.test(text);

    // 1) 槓桿 / 反向先判斷，因為這類商品風險屬性優先於區域分類
    if (/反向|反1|反一|INVERSE|BEAR|放空|看空/.test(text) || id.endsWith('R')) {
        if (isCommodity) return { categoryKey: 'leveraged', subKey: '商品槓桿 / 反向' };
        if (isOverseas) return { categoryKey: 'leveraged', subKey: '海外反向' };
        if (/^00/.test(id)) return { categoryKey: 'leveraged', subKey: '台股反向' };
        return { categoryKey: 'leveraged', subKey: '槓桿反向其他' };
    }

    if (/正2|槓桿|LEVERAGE|LEVERAGED|BULL|2X/.test(text) || id.endsWith('L')) {
        if (isCommodity) return { categoryKey: 'leveraged', subKey: '商品槓桿 / 反向' };
        if (isOverseas) return { categoryKey: 'leveraged', subKey: '海外槓桿' };
        if (/^00/.test(id)) return { categoryKey: 'leveraged', subKey: '台股槓桿' };
        return { categoryKey: 'leveraged', subKey: '槓桿反向其他' };
    }

    // 2) 債券 ETF
    if (/債|公債|公司債|金融債|高收益|投資級|新興債|信用債|A級|AA|BBB|TREASURY|BOND/.test(text) || id.endsWith('B')) {
        if (/高收益|非投資|HY|HIGH YIELD/.test(text)) {
            return { categoryKey: 'bond', subKey: '高收益債' };
        }
        if (/新興|EM|亞洲債|EMERGING/.test(text)) {
            return { categoryKey: 'bond', subKey: '新興市場債' };
        }
        if (/金融債|銀行債|BANK/.test(text)) {
            return { categoryKey: 'bond', subKey: '金融債' };
        }
        if (/投資級|公司債|A級|AA|BBB|CORPORATE/.test(text)) {
            return { categoryKey: 'bond', subKey: '投資級公司債' };
        }
        if (/美債|美國公債|TREASURY|20年|25年|30年|長天期|長債/.test(text)) {
            return { categoryKey: 'bond', subKey: '美國公債長天期' };
        }
        if (/美國公債|美債|10年|7-10|1-3|3-7|短天期|中天期|美元/.test(text)) {
            return { categoryKey: 'bond', subKey: '美國公債中短天期' };
        }
        return { categoryKey: 'bond', subKey: '債券其他' };
    }

    // 3) 商品 / 期貨 ETF
    if (/黃金|白銀|貴金屬|GOLD|SILVER/.test(text)) {
        return { categoryKey: 'commodity', subKey: '黃金 / 貴金屬' };
    }

    if (/原油|石油|能源|OIL|BRENT|WTI/.test(text)) {
        return { categoryKey: 'commodity', subKey: '原油 / 能源' };
    }

    if (/期貨|商品|COMMODITY|銅|農產品/.test(text)) {
        return { categoryKey: 'commodity', subKey: '期貨 / 商品籃子' };
    }

    // 4) 海外股票 ETF
    if (isOverseas) {
        if (/高股息|高息|收益|股息|股利|配息|月配|季配|DIVIDEND|INCOME/.test(text)) {
            return { categoryKey: 'overseas', subKey: '海外高股息 / 收益' };
        }
        if (/NASDAQ|那斯達克|費城|FANG|科技|半導體|SOX|PHLX|TECH|SEMICONDUCTOR/.test(text)) {
            return { categoryKey: 'overseas', subKey: '美股科技 / NASDAQ' };
        }
        if (/美國|美股|標普|S&P|SP500|道瓊|NYSE|USA|US/.test(text)) {
            return { categoryKey: 'overseas', subKey: '美股大盤 / 標普' };
        }
        if (/中國|大陸|中證|上證|滬深|香港|恒生|恆生|A股|H股|港股|CHINA|HANG SENG|CSI/.test(text)) {
            return { categoryKey: 'overseas', subKey: '中國 / 香港' };
        }
        if (/日本|韓國|JAPAN|KOREA/.test(text)) {
            return { categoryKey: 'overseas', subKey: '日本 / 韓國' };
        }
        if (/越南|亞洲|東協|亞太|VIETNAM|ASEAN|ASIA/.test(text)) {
            return { categoryKey: 'overseas', subKey: '越南 / 東協 / 亞洲' };
        }
        if (/印度|新興|巴西|拉美|INDIA|EMERGING|BRAZIL/.test(text)) {
            return { categoryKey: 'overseas', subKey: '印度 / 新興市場' };
        }
        if (/全球|世界|MSCI|WORLD|GLOBAL|已開發/.test(text)) {
            return { categoryKey: 'overseas', subKey: '全球 / 已開發市場' };
        }
        return { categoryKey: 'overseas', subKey: '海外其他主題' };
    }

    // 5) 台股 ETF：補強容易被誤丟到其他的關鍵字
    if (/月配|月月配|月收益|每月|季配|配息|收益|股息|股利|高股息|高息|優息|DIVIDEND/.test(text)) {
        if (/月配|月月配|月收益|每月/.test(text)) {
            return { categoryKey: 'taiwan', subKey: '月配息 / 季配息' };
        }
        return { categoryKey: 'taiwan', subKey: '高股息 / 收益型' };
    }

    if (/AI|5G|電動車|機器人|雲端|數位|未來|創新|電池|自動化|INNOVATION/.test(text)) {
        return { categoryKey: 'taiwan', subKey: 'AI / 電動車 / 5G' };
    }

    if (/科技|半導體|電子|資訊|通訊|生技|醫療|綠能|TECH|SEMICONDUCTOR/.test(text)) {
        return { categoryKey: 'taiwan', subKey: '科技 / 半導體' };
    }

    if (/ESG|永續|低碳|碳|公司治理|治理|社會責任|SUSTAIN/.test(text)) {
        return { categoryKey: 'taiwan', subKey: 'ESG / 永續 / 低碳' };
    }

    if (/金融|銀行|保險|傳產|航運|營建|REIT|不動產|產業|主題/.test(text)) {
        return { categoryKey: 'taiwan', subKey: '金融 / 產業主題' };
    }

    if (/低波|低波動|低貝塔|動能|品質|價值|成長|多因子|等權重|優選|精選|特選|SMART|MOMENTUM|QUALITY|VALUE/.test(text)) {
        return { categoryKey: 'taiwan', subKey: '低波動 / 因子策略' };
    }

    if (/中型100|中小|小型|富櫃|櫃買|櫃買50|中小型|中型|SMALL|MID/.test(text)) {
        return { categoryKey: 'taiwan', subKey: '中小型 / 櫃買' };
    }

    if (/台灣50|臺灣50|台50|臺50|MSCI台灣|MSCI臺灣|摩台|加權|大盤|市值|藍籌|高價|卓越|台股|臺股|台灣|臺灣|TAIWAN/.test(text)) {
        return { categoryKey: 'taiwan', subKey: '市值型 / 大盤' };
    }

    // 6) 前面已經排除債券、海外、商品、槓桿反向；剩下 00 開頭多數是台股主題 ETF
    if (/^00/.test(id)) {
        return { categoryKey: 'taiwan', subKey: '台股其他主題' };
    }

    return { categoryKey: 'other', subKey: '特殊策略 / 無法判斷' };
}

function mergeData() {
    mergedCategories = JSON.parse(JSON.stringify(apiCategories));

    // 如果瀏覽器之前的下架紀錄剛好把全部抓到的 ETF 都藏起來，畫面會空白。
    // 這裡只在「幾乎全被藏起來」時自動清空舊下架紀錄，避免誤判成資料沒有進來。
    const allFetchedIds = allEtfList.map(item => item.id);
    const deletedFetchedCount = allFetchedIds.filter(id => deletedETFs.includes(id)).length;
    if (allFetchedIds.length > 0 && deletedFetchedCount >= allFetchedIds.length * 0.9) {
        deletedETFs = [];
        localStorage.setItem('realDeletedETFs', JSON.stringify(deletedETFs));
    }

    Object.keys(mergedCategories).forEach(catKey => {
        Object.keys(mergedCategories[catKey]).forEach(subKey => {
            if (subKey === 'name') return;
            mergedCategories[catKey][subKey] = mergedCategories[catKey][subKey].filter(etf => !deletedETFs.includes(etf.id));
        });
    });
    addedETFs.forEach(newEtf => {
        const catKey = newEtf.categoryKey;
        const subKey = newEtf.subKey;
        if (mergedCategories[catKey]) {
            if (!mergedCategories[catKey][subKey]) mergedCategories[catKey][subKey] = [];
            if (!mergedCategories[catKey][subKey].some(e => e.id === newEtf.id)) {
                mergedCategories[catKey][subKey].push({ id: newEtf.id, name: newEtf.name });
            }
        }
    });
}

function renderData() {
    const searchQuery = document.getElementById('searchInput').value.trim().toLowerCase();
    if (searchQuery !== '') { renderSearchResults(searchQuery); return; }
    if (currentView === 'menu') { renderMainMenu(); } else if (currentView === 'category' && activeCategoryKey) { renderCategoryView(activeCategoryKey); }
}

function renderMainMenu() {
    const container = document.getElementById('etf-container');
    container.innerHTML = '';
    const menuGrid = document.createElement('div');
    menuGrid.className = 'category-menu';
    Object.entries(mergedCategories).forEach(([categoryKey, category]) => {
        const btn = document.createElement('button');
        btn.className = 'menu-btn';
        btn.textContent = category.name;
        btn.onclick = () => { currentView = 'category'; activeCategoryKey = categoryKey; renderCategoryView(categoryKey); };
        menuGrid.appendChild(btn);
    });
    container.appendChild(menuGrid);
}

function renderCategoryView(categoryKey) {
    const container = document.getElementById('etf-container');
    container.innerHTML = '';
    const category = mergedCategories[categoryKey];
    if (!category) return;

    const btnBack = document.createElement('button');
    btnBack.className = 'btn-back';
    btnBack.textContent = '⬅ 返回分類選單';
    btnBack.onclick = () => { currentView = 'menu'; activeCategoryKey = null; renderMainMenu(); };
    container.appendChild(btnBack);

    const section = document.createElement('div');
    section.className = 'category';

    const title = document.createElement('div');
    title.className = 'category-title';
    title.textContent = category.name;
    section.appendChild(title);

    let renderedCount = 0;

    Object.entries(category).forEach(([subKey, etfList]) => {
        if (subKey === 'name') return;
        if (!Array.isArray(etfList) || etfList.length === 0) return;

        const subTitle = document.createElement('div');
        subTitle.className = 'sub-title';
        subTitle.textContent = subKey;
        section.appendChild(subTitle);

        const grid = document.createElement('div');
        grid.className = 'grid';
        etfList.forEach(etf => {
            grid.appendChild(createETFCard(etf));
            renderedCount++;
        });
        section.appendChild(grid);
    });

    if (renderedCount === 0) {
        const empty = document.createElement('div');
        empty.style.color = '#7b80a1';
        empty.style.marginTop = '20px';
        empty.textContent = '此分類目前沒有 ETF。可以返回分類選單查看其他分類，或用上方搜尋 ETF 代號。';
        section.appendChild(empty);
    }

    container.appendChild(section);
}

function renderSearchResults(query) {
    const container = document.getElementById('etf-container');
    container.innerHTML = `<button class="btn-back" onclick="document.getElementById('searchInput').value=''; renderData()">⬅ 返回</button>`;
    const grid = document.createElement('div');
    grid.className = 'grid';
    Object.values(mergedCategories).forEach(category => {
        Object.entries(category).forEach(([subKey, etfList]) => {
            if (subKey === 'name') return;
            etfList.forEach(etf => {
                if (etf.id.toLowerCase().includes(query) || etf.name.toLowerCase().includes(query)) {
                    grid.appendChild(createETFCard(etf));
                }
            });
        });
    });
    container.appendChild(grid);
}

function createETFCard(etf) {
    const quote = globalQuoteMap[etf.id];
    const price = quote?.price ?? '新上市';
    const hasChangePct = quote && quote.changePct !== null && quote.changePct !== undefined;
    const changePct = Number(quote?.changePct ?? 0);
    const pd = globalPdMap.hasOwnProperty(etf.id) ? globalPdMap[etf.id] : null;
    const setting = getDisciplineSetting(etf.id);
    const purchaseCost = setting.cost;
    const costRate = calculateCostRate(price, purchaseCost);
    const stopLossPrice = calculateTargetPrice(purchaseCost, setting.stopLossPct, 'loss');
    const takeProfitPrice = calculateTargetPrice(purchaseCost, setting.takeProfitPct, 'profit');

    const card = document.createElement('div');
    card.className = 'card';
    
    // ⭐ 新增：點擊卡片顯示目前已抓到的 ETF 詳細資料
    card.onclick = () => showETFDetails(etf.id, etf.name);

    card.innerHTML = `
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div class="symbol">${etf.id}</div>
            <button class="btn-delete" title="下架此ETF" onclick="event.stopPropagation(); removeETF('${etf.id}')">🗑️</button>
        </div>
        <div class="name">${etf.name}</div>
        <div class="price">${price}</div>
        <div class="change ${changePct >= 0 ? 'up' : 'down'}">${hasChangePct ? changePct.toFixed(2) + '%' : '--'}</div>
        <div style="margin-top:8px; font-size:14px; font-weight:bold; color: ${pd === null ? '#888' : (pd >= 0 ? '#ff5555' : '#00ff66')}">
            ${pd === null ? '折溢價: 未知' : (pd >= 0 ? '溢價 ' + pd + '%' : '折價 ' + Math.abs(pd) + '%')}
        </div>
        <div style="margin-top:6px; font-size:13px; color:${costRate === null ? '#888' : (costRate >= 0 ? '#00ff66' : '#ff5555')}">
            ${costRate === null ? '成本率：尚未設定' : '成本率：' + formatSignedPercent(costRate)}
        </div>
        <div class="card-discipline-row">
            <div>停損 <span>${stopLossPrice === null ? '--' : formatPrice(stopLossPrice)}</span></div>
            <div>獲利 <span>${takeProfitPrice === null ? '--' : formatPrice(takeProfitPrice)}</span></div>
        </div>
    `;
    return card;
}

function loadDisciplineSettings() {
    const oldCosts = JSON.parse(localStorage.getItem('etfPurchaseCosts')) || {};
    const savedSettings = JSON.parse(localStorage.getItem('etfDisciplineSettings')) || {};
    Object.entries(oldCosts).forEach(([id, cost]) => {
        if (!savedSettings[id]) savedSettings[id] = { cost };
    });
    return savedSettings;
}

function getDisciplineSetting(id) {
    const saved = etfDisciplineSettings[id] || {};
    return {
        cost: toNumberOrNull(saved.cost),
        stopLossPct: toNumberOrNull(saved.stopLossPct) ?? 10,
        takeProfitPct: toNumberOrNull(saved.takeProfitPct) ?? 20
    };
}

function calculateCostRate(currentPrice, purchaseCost) {
    const current = toNumberOrNull(currentPrice);
    const cost = toNumberOrNull(purchaseCost);
    if (current === null || cost === null || cost <= 0) return null;
    return ((current - cost) / cost) * 100;
}

function calculateTargetPrice(purchaseCost, percent, direction) {
    const cost = toNumberOrNull(purchaseCost);
    const pct = toNumberOrNull(percent);
    if (cost === null || cost <= 0 || pct === null || pct < 0) return null;
    return direction === 'loss' ? cost * (1 - pct / 100) : cost * (1 + pct / 100);
}

function formatPrice(value) {
    if (!Number.isFinite(value)) return '--';
    return value.toFixed(2);
}

function formatSignedPercent(value) {
    if (!Number.isFinite(value)) return '--';
    return `${value > 0 ? '+' : ''}${value.toFixed(2)}%`;
}

// ⭐ 使用 etf_crawler.php 已抓到的 120 筆資料顯示詳細資訊，不再強制依賴 localhost:4000
async function showETFDetails(id, name) {
    const modal = document.getElementById('detailsModal');
    const title = document.getElementById('modalTitle');
    const subtitle = document.getElementById('modalSubtitle');
    const body = document.getElementById('modalBody');
    const quote = globalQuoteMap[id] || {};
    const pd = globalPdMap.hasOwnProperty(id) ? globalPdMap[id] : null;
    const setting = getDisciplineSetting(id);
    const purchaseCost = setting.cost;
    const costRate = calculateCostRate(quote.price, purchaseCost);
    const stopLossPrice = calculateTargetPrice(purchaseCost, setting.stopLossPct, 'loss');
    const takeProfitPrice = calculateTargetPrice(purchaseCost, setting.takeProfitPct, 'profit');

    modal.style.display = 'flex';
    title.innerText = `${id} ${name}`;
    subtitle.innerText = `資料來源：etf_crawler.php 即時資料`;
    
    body.innerHTML = `
        <div class="details-grid">
            <div class="detail-item">
                <div class="detail-label">💰 市價</div>
                <div class="detail-value">${quote.price ?? '--'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">💵 預估淨值 NAV</div>
                <div class="detail-value">${quote.nav ?? '--'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">📈 折溢價</div>
                <div class="detail-value">${pd === null ? '--' : pd + '%'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">🔎 ETF 代號</div>
                <div class="detail-value">${id}</div>
            </div>
        </div>
        <div class="cost-panel">
            <div class="cost-panel-title">🧮 投資紀律設定</div>
            <div class="discipline-inputs">
                <div class="discipline-field">
                    <label for="purchaseCostInput">平均買入成本</label>
                    <input
                        class="cost-input"
                        id="purchaseCostInput"
                        type="number"
                        min="0.0001"
                        step="0.01"
                        inputmode="decimal"
                        value="${purchaseCost ?? ''}"
                        placeholder="例如 100"
                        oninput="previewDiscipline('${id}')"
                        onkeydown="if(event.key === 'Enter') saveDisciplineSetting('${id}')"
                    >
                </div>
                <div class="discipline-field">
                    <label for="stopLossPctInput">停損比例 %</label>
                    <input
                        class="cost-input"
                        id="stopLossPctInput"
                        type="number"
                        min="0"
                        step="0.1"
                        inputmode="decimal"
                        value="${setting.stopLossPct}"
                        oninput="previewDiscipline('${id}')"
                        onkeydown="if(event.key === 'Enter') saveDisciplineSetting('${id}')"
                    >
                </div>
                <div class="discipline-field">
                    <label for="takeProfitPctInput">獲利比例 %</label>
                    <input
                        class="cost-input"
                        id="takeProfitPctInput"
                        type="number"
                        min="0"
                        step="0.1"
                        inputmode="decimal"
                        value="${setting.takeProfitPct}"
                        oninput="previewDiscipline('${id}')"
                        onkeydown="if(event.key === 'Enter') saveDisciplineSetting('${id}')"
                    >
                </div>
            </div>
            <div class="cost-input-row">
                <button class="btn-save-cost" onclick="saveDisciplineSetting('${id}')">儲存設定</button>
            </div>
            <div class="discipline-results">
                <div class="discipline-result-card">
                    <div class="discipline-result-label">成本率</div>
                    <div class="discipline-result-value" id="costRateResult" style="color:${costRate === null ? '#888' : (costRate >= 0 ? '#00ff66' : '#ff5555')}">
                        ${costRate === null ? '--' : formatSignedPercent(costRate)}
                    </div>
                </div>
                <div class="discipline-result-card">
                    <div class="discipline-result-label">建議停損價</div>
                    <div class="discipline-result-value" id="stopLossPriceResult" style="color:#ff5555;">
                        ${stopLossPrice === null ? '--' : formatPrice(stopLossPrice)}
                    </div>
                </div>
                <div class="discipline-result-card">
                    <div class="discipline-result-label">建議獲利價</div>
                    <div class="discipline-result-value" id="takeProfitPriceResult" style="color:#00ff66;">
                        ${takeProfitPrice === null ? '--' : formatPrice(takeProfitPrice)}
                    </div>
                </div>
            </div>
            <div class="cost-help">停損/獲利價依平均成本試算，預設停損 10%、獲利 20%；未含交易手續費與稅費。清空平均成本後按儲存可移除該 ETF 紀錄。</div>
        </div>
    `;
}

function previewDiscipline(id) {
    const costInput = document.getElementById('purchaseCostInput');
    const stopLossInput = document.getElementById('stopLossPctInput');
    const takeProfitInput = document.getElementById('takeProfitPctInput');
    const costRateResult = document.getElementById('costRateResult');
    const stopLossResult = document.getElementById('stopLossPriceResult');
    const takeProfitResult = document.getElementById('takeProfitPriceResult');
    if (!costInput || !costRateResult || !stopLossResult || !takeProfitResult) return;

    const rate = calculateCostRate(globalQuoteMap[id]?.price, costInput.value);
    const stopLossPrice = calculateTargetPrice(costInput.value, stopLossInput?.value, 'loss');
    const takeProfitPrice = calculateTargetPrice(costInput.value, takeProfitInput?.value, 'profit');
    costRateResult.textContent = rate === null ? '--' : formatSignedPercent(rate);
    costRateResult.style.color = rate === null ? '#888' : (rate >= 0 ? '#00ff66' : '#ff5555');
    stopLossResult.textContent = stopLossPrice === null ? '--' : formatPrice(stopLossPrice);
    takeProfitResult.textContent = takeProfitPrice === null ? '--' : formatPrice(takeProfitPrice);
}

function saveDisciplineSetting(id) {
    const costInput = document.getElementById('purchaseCostInput');
    const stopLossInput = document.getElementById('stopLossPctInput');
    const takeProfitInput = document.getElementById('takeProfitPctInput');
    if (!costInput || !stopLossInput || !takeProfitInput) return;

    const rawCost = costInput.value.trim();
    if (rawCost === '') {
        delete etfDisciplineSettings[id];
        localStorage.setItem('etfDisciplineSettings', JSON.stringify(etfDisciplineSettings));
        const legacyCosts = JSON.parse(localStorage.getItem('etfPurchaseCosts')) || {};
        delete legacyCosts[id];
        localStorage.setItem('etfPurchaseCosts', JSON.stringify(legacyCosts));
        previewDiscipline(id);
        renderData();
        return;
    }

    const cost = toNumberOrNull(rawCost);
    const stopLossPct = toNumberOrNull(stopLossInput.value);
    const takeProfitPct = toNumberOrNull(takeProfitInput.value);
    if (cost === null || cost <= 0) {
        alert('平均買入成本必須是大於 0 的數字。');
        costInput.focus();
        return;
    }
    if (stopLossPct === null || stopLossPct < 0 || takeProfitPct === null || takeProfitPct < 0) {
        alert('停損比例與獲利比例必須是 0 以上的數字。');
        return;
    }

    etfDisciplineSettings[id] = { cost, stopLossPct, takeProfitPct };
    localStorage.setItem('etfDisciplineSettings', JSON.stringify(etfDisciplineSettings));
    localStorage.setItem('etfPurchaseCosts', JSON.stringify(Object.fromEntries(
        Object.entries(etfDisciplineSettings).map(([etfId, setting]) => [etfId, setting.cost])
    )));
    costInput.value = cost;
    stopLossInput.value = stopLossPct;
    takeProfitInput.value = takeProfitPct;
    previewDiscipline(id);
    renderData();
}

// ⭐ 新增：關閉 Modal 的邏輯
function closeModal(event) {
    if (event) {
        // 如果是點擊在黑屏遮罩上（而不是白色框框內），才關閉
        if (event.target.id === 'detailsModal') {
            document.getElementById('detailsModal').style.display = 'none';
        }
    } else {
        // 點擊打叉按鈕直接關閉
        document.getElementById('detailsModal').style.display = 'none';
    }
}

function toggleAdminPanel() { const panel = document.getElementById('adminPanel'); panel.style.display = panel.style.display === 'block' ? 'none' : 'block'; }
function initCategorySelect() { const select = document.getElementById('newCategory'); select.innerHTML = '<option value="">--請選擇大分類--</option>'; Object.entries(apiCategories).forEach(([key, value]) => { const opt = document.createElement('option'); opt.value = key; opt.textContent = value.name; select.appendChild(opt); }); }
function updateSubCategoryOptions() { const select = document.getElementById('newCategory'); const input = document.getElementById('newSubKey'); const category = apiCategories?.[select.value]; if (!category) { input.placeholder = '子分類標題 (如: 高股息、市值型)'; return; } const subKeys = Object.keys(category).filter(key => key !== 'name'); input.placeholder = subKeys.length ? '子分類標題，例如：' + subKeys.slice(0, 3).join('、') : '子分類標題'; }

function submitNewETF() { 
    const id = document.getElementById('newId').value.trim().toUpperCase(); 
    const name = document.getElementById('newName').value.trim(); 
    const categoryKey = document.getElementById('newCategory').value; 
    const subKey = document.getElementById('newSubKey').value.trim(); 
    if (!id || !name || !categoryKey || !subKey) { alert('請填寫資訊'); return; } 
    deletedETFs = deletedETFs.filter(dId => dId !== id); 
    localStorage.setItem('realDeletedETFs', JSON.stringify(deletedETFs)); 
    addedETFs.push({ id, name, categoryKey, subKey }); 
    localStorage.setItem('realAddedETFs', JSON.stringify(addedETFs)); 
    alert(`🎉 恭喜！新上市 ETF [${id} ${name}] 已成功加入 [${subKey}] 分類！`);
    location.reload(); 
}

function removeETF(etfId) { if (confirm('確定下架?')) { addedETFs = addedETFs.filter(e => e.id !== etfId); localStorage.setItem('realAddedETFs', JSON.stringify(addedETFs)); deletedETFs.push(etfId); localStorage.setItem('realDeletedETFs', JSON.stringify(deletedETFs)); location.reload(); } }
function handleSearch() { renderData(); }

loadETF();
setInterval(loadETF, 15000);
</script>
</body>
</html>
