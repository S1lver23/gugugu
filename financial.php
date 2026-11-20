<?php
session_start();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>財報專區 - 古估股</title>
  <link rel="stylesheet" href="styles/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
  <style>
    :root {
      --finance-bg: #0d0f1a;
      --finance-surface: #1a1c2a;
      --finance-border: #3a3d5a;
      --finance-green: #64ffda;
      --finance-pink: #f000ff;
      --finance-text: #e8eaff;
      --finance-muted: #8d93b5;
      --finance-danger: #ff6b6b;
      --finance-warning: #ffd166;
    }

    body {
      background: var(--finance-bg);
      color: var(--finance-text);
    }

    .finance-page {
      max-width: 1180px;
      margin: 0 auto;
      padding: 28px 20px 70px;
    }

    .finance-hero {
      display: grid;
      grid-template-columns: minmax(0, 1.2fr) minmax(280px, 0.8fr);
      gap: 20px;
      align-items: stretch;
      margin-bottom: 22px;
    }

    .finance-title-panel,
    .finance-search-panel,
    .finance-panel {
      background: var(--finance-surface);
      border: 1px solid var(--finance-border);
      border-radius: 12px;
      padding: 22px;
    }

    .finance-title-panel h2 {
      margin: 0 0 10px;
      font-size: 2rem;
      color: #fff;
    }

    .finance-title-panel p,
    .finance-help,
    .finance-status {
      color: var(--finance-muted);
      line-height: 1.7;
    }

    .finance-search-panel label {
      display: block;
      color: var(--finance-muted);
      margin-bottom: 8px;
      font-size: 0.92rem;
    }

    .finance-search-row {
      display: flex;
      gap: 10px;
    }

    .finance-search-row input {
      flex: 1;
      min-width: 0;
      border: 1px solid var(--finance-border);
      border-radius: 8px;
      background: #0d0f1a;
      color: #fff;
      padding: 12px 13px;
      outline: none;
      font-size: 1rem;
    }

    .finance-search-row input:focus {
      border-color: var(--finance-green);
      box-shadow: 0 0 0 2px rgba(100, 255, 218, 0.12);
    }

    .finance-search-row button,
    .quick-symbol {
      border: none;
      border-radius: 8px;
      background: var(--finance-green);
      color: #111;
      padding: 0 18px;
      font-weight: 700;
      cursor: pointer;
    }

    .quick-list {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-top: 14px;
    }

    .quick-symbol {
      background: #25283d;
      color: #fff;
      border: 1px solid var(--finance-border);
      padding: 8px 10px;
    }

    .quick-symbol:hover {
      border-color: var(--finance-green);
      color: var(--finance-green);
    }

    .finance-meta {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin: 0 0 18px;
      color: var(--finance-muted);
    }

    .finance-badge {
      border: 1px solid var(--finance-border);
      border-radius: 999px;
      padding: 6px 10px;
      background: #101322;
    }

    .metric-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 14px;
      margin-bottom: 18px;
    }

    .metric-card {
      background: #101322;
      border: 1px solid var(--finance-border);
      border-radius: 10px;
      padding: 16px;
      min-height: 112px;
    }

    .metric-label {
      color: var(--finance-muted);
      font-size: 0.88rem;
      margin-bottom: 10px;
    }

    .metric-value {
      color: #fff;
      font-size: 1.45rem;
      font-weight: 800;
      word-break: break-word;
    }

    .metric-sub {
      margin-top: 8px;
      color: var(--finance-muted);
      font-size: 0.82rem;
    }

    .finance-columns {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 18px;
    }

    .finance-panel h3 {
      margin: 0 0 14px;
      color: #fff;
      font-size: 1.25rem;
    }

    .finance-table {
      width: 100%;
      border-collapse: collapse;
    }

    .finance-table th,
    .finance-table td {
      border-bottom: 1px solid var(--finance-border);
      padding: 12px 8px;
      text-align: left;
    }

    .finance-table th {
      color: var(--finance-muted);
      font-weight: 500;
    }

    .finance-table td:last-child {
      text-align: right;
      color: #fff;
      font-weight: 700;
    }

    .up { color: #00ff66 !important; }
    .down { color: var(--finance-danger) !important; }
    .empty-state {
      border: 1px dashed var(--finance-border);
      border-radius: 12px;
      padding: 36px 20px;
      text-align: center;
      color: var(--finance-muted);
      background: rgba(255,255,255,0.03);
    }

    @media (max-width: 860px) {
      .finance-hero,
      .finance-columns,
      .metric-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 460px) {
      .finance-search-row {
        flex-direction: column;
      }

      .finance-search-row button {
        padding: 12px;
      }
    }
  </style>
<?php require __DIR__ . '/includes/site-head.php'; ?>
</head>
<body class="site-layout">
<?php require __DIR__ . '/includes/site-header.php'; ?>
  

  <main class="finance-page">
    <section class="finance-hero">
      <div class="finance-title-panel">
        <h2>財報專區</h2>
        <p>輸入上市股票代號，快速查看最近季度損益表、資產負債表與月營收重點。資料查不到時會直接顯示無資料，不使用估算或假資料。</p>
      </div>

      <div class="finance-search-panel">
        <label for="financeSymbol">股票代號</label>
        <div class="finance-search-row">
          <input id="financeSymbol" type="text" placeholder="例如：2330" maxlength="6" autocomplete="off">
          <button type="button" onclick="loadFinancialReport()">查詢</button>
        </div>
        <div class="quick-list">
          <button class="quick-symbol" onclick="quickSearch('2330')">2330 台積電</button>
          <button class="quick-symbol" onclick="quickSearch('1216')">1216 統一</button>
          <button class="quick-symbol" onclick="quickSearch('2618')">2618 長榮航</button>
        </div>
      </div>
    </section>

    <div id="financeStatus" class="finance-status"></div>
    <section id="financeResult" class="empty-state">請輸入股票代號開始查詢財報。</section>
  </main>
  <script src="scripts/script.js?v=<?= filemtime(__DIR__ . '/scripts/script.js') ?>"></script>
  <script>
    function toDisplayNumber(value, unit = '') {
      if (value === null || value === undefined || value === '') return '--';
      const num = Number(value);
      if (!Number.isFinite(num)) return '--';
      return num.toLocaleString('zh-TW', { maximumFractionDigits: 2 }) + unit;
    }

    function toMillionDisplay(value) {
      if (value === null || value === undefined || value === '') return '--';
      const num = Number(value);
      if (!Number.isFinite(num)) return '--';
      return (num / 1000).toLocaleString('zh-TW', { maximumFractionDigits: 2 }) + ' 百萬元';
    }

    function pctDisplay(value) {
      if (value === null || value === undefined || value === '') return '--';
      const num = Number(value);
      if (!Number.isFinite(num)) return '--';
      const sign = num > 0 ? '+' : '';
      return sign + num.toFixed(2) + '%';
    }

    function pctClass(value) {
      const num = Number(value);
      if (!Number.isFinite(num) || num === 0) return '';
      return num > 0 ? 'up' : 'down';
    }

    function periodText(period) {
      if (!period || !period.year || !period.quarter) return '最近可得季度';
      return `${period.year} 年第 ${period.quarter} 季`;
    }

    function quickSearch(symbol) {
      document.getElementById('financeSymbol').value = symbol;
      loadFinancialReport();
    }

    async function loadFinancialReport() {
      const input = document.getElementById('financeSymbol');
      const status = document.getElementById('financeStatus');
      const result = document.getElementById('financeResult');
      const symbol = input.value.trim().toUpperCase();
      input.value = symbol;

      if (!symbol) {
        status.textContent = '';
        result.className = 'empty-state';
        result.textContent = '請先輸入股票代號。';
        return;
      }

      status.textContent = '讀取財報中...';
      result.className = 'empty-state';
      result.textContent = '正在向證交所 OpenAPI 查詢資料。';

      try {
        const response = await fetch(`financial_api.php?symbol=${encodeURIComponent(symbol)}&_=${Date.now()}`);
        const data = await response.json();
        if (!data.ok) {
          status.textContent = '';
          result.className = 'empty-state';
          result.textContent = data.message || '查不到財報資料。';
          return;
        }

        status.textContent = `資料來源：證交所 OpenAPI，${new Date().toLocaleString('zh-TW', { hour12: false })} 查詢`;
        renderFinancialReport(data);
      } catch (error) {
        status.textContent = '';
        result.className = 'empty-state';
        result.textContent = '財報資料讀取失敗，請確認網路或稍後再試。';
      }
    }

    function escapeFinanceText(value) { return String(value ?? '').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
    function renderFinancialReport(data) {
      const result = document.getElementById('financeResult');
      const income = data.income || {};
      const balance = data.balance || {};
      const monthly = data.monthlyRevenue || {};
      result.className = '';
      result.innerHTML = `
        <div class="finance-meta">
          <span class="finance-badge">${escapeFinanceText(data.symbol)} ${escapeFinanceText(data.companyName || '')}</span>
          <span class="finance-badge">${periodText(data.period)}</span>
          <span class="finance-badge">${escapeFinanceText(data.industryType || '產業別未標示')}</span>
        </div>

        <div class="metric-grid">
          <div class="metric-card">
            <div class="metric-label">營業收入</div>
            <div class="metric-value">${toMillionDisplay(income.revenue)}</div>
            <div class="metric-sub">季度損益表</div>
          </div>
          <div class="metric-card">
            <div class="metric-label">EPS</div>
            <div class="metric-value">${toDisplayNumber(income.eps, ' 元')}</div>
            <div class="metric-sub">基本每股盈餘</div>
          </div>
          <div class="metric-card">
            <div class="metric-label">毛利率</div>
            <div class="metric-value">${pctDisplay(income.grossMargin)}</div>
            <div class="metric-sub">營業毛利 / 營收</div>
          </div>
          <div class="metric-card">
            <div class="metric-label">負債比</div>
            <div class="metric-value">${pctDisplay(balance.debtRatio)}</div>
            <div class="metric-sub">負債總額 / 資產總額</div>
          </div>
        </div>

        <div class="finance-columns">
          <section class="finance-panel">
            <h3>損益表重點</h3>
            <table class="finance-table">
              <tbody>
                <tr><th>營業收入</th><td>${toMillionDisplay(income.revenue)}</td></tr>
                <tr><th>營業毛利</th><td>${toMillionDisplay(income.grossProfit)}</td></tr>
                <tr><th>營業利益</th><td>${toMillionDisplay(income.operatingIncome)}</td></tr>
                <tr><th>本期淨利</th><td>${toMillionDisplay(income.netIncome)}</td></tr>
                <tr><th>營業利益率</th><td>${pctDisplay(income.operatingMargin)}</td></tr>
                <tr><th>淨利率</th><td>${pctDisplay(income.netMargin)}</td></tr>
              </tbody>
            </table>
          </section>

          <section class="finance-panel">
            <h3>資產負債表重點</h3>
            <table class="finance-table">
              <tbody>
                <tr><th>資產總額</th><td>${toMillionDisplay(balance.assets)}</td></tr>
                <tr><th>負債總額</th><td>${toMillionDisplay(balance.liabilities)}</td></tr>
                <tr><th>權益總額</th><td>${toMillionDisplay(balance.equity)}</td></tr>
                <tr><th>每股參考淨值</th><td>${toDisplayNumber(balance.bookValuePerShare, ' 元')}</td></tr>
                <tr><th>負債比</th><td>${pctDisplay(balance.debtRatio)}</td></tr>
              </tbody>
            </table>
          </section>

          <section class="finance-panel">
            <h3>月營收</h3>
            <table class="finance-table">
              <tbody>
                <tr><th>資料年月</th><td>${monthly.month || '--'}</td></tr>
                <tr><th>當月營收</th><td>${toMillionDisplay(monthly.currentMonthRevenue)}</td></tr>
                <tr><th>上月營收</th><td>${toMillionDisplay(monthly.lastMonthRevenue)}</td></tr>
                <tr><th>去年同月營收</th><td>${toMillionDisplay(monthly.lastYearMonthRevenue)}</td></tr>
                <tr><th>月增率</th><td class="${pctClass(monthly.mom)}">${pctDisplay(monthly.mom)}</td></tr>
                <tr><th>年增率</th><td class="${pctClass(monthly.yoy)}">${pctDisplay(monthly.yoy)}</td></tr>
              </tbody>
            </table>
          </section>

          <section class="finance-panel">
            <h3>資料說明</h3>
            <p class="finance-help">本頁顯示證交所 OpenAPI 可取得的上市公司公開資料，金額欄位以「百萬元」呈現。財報公布有時間差，最新交易日股價與最新財報季度不一定相同。</p>
          </section>
        </div>
      `;
    }

    document.getElementById('financeSymbol').addEventListener('keydown', event => {
      if (event.key === 'Enter') loadFinancialReport();
    });

    const params = new URLSearchParams(window.location.search);
    const initialSymbol = params.get('symbol');
    if (initialSymbol) {
      document.getElementById('financeSymbol').value = initialSymbol;
      loadFinancialReport();
    }
  </script>
</body>
</html>
