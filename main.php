<?php
// 登入檢查和初始化
session_start();

// 初始化快照數據
$snapshotFile = __DIR__ . '/data/latest-market-cache.json';
$snapshot = [
  'updatedAt' => null,
  'etf' => [],
  'sectors' => [
    'food' => [],
    'travel' => [],
    'aviation' => []
  ]
];

if (file_exists($snapshotFile)) {
  $raw = file_get_contents($snapshotFile);
  $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
  $decoded = json_decode($raw, true);
  if (is_array($decoded)) {
    $snapshot = array_replace_recursive($snapshot, $decoded);
  }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>古估股</title>
  <link rel="stylesheet" href="styles/style.css?v=<?= filemtime(__DIR__ . '/styles/style.css') ?>" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
  <script src="https://unpkg.com/lightweight-charts/dist/lightweight-charts.standalone.production.js"></script>

  <style>
    /* 停損 / 獲利了結提醒：只調整本區塊，不影響首頁其他排版 */
    .risk-alert-panel {
      position: relative;
      background: rgba(255,255,255,0.05);
      border: 1px solid #333;
      border-radius: 14px;
      padding: 24px;
      margin: 0 20px 20px;
    }
    .risk-panel-topbar {
      position: relative;
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 18px;
    }
    .risk-panel-intro h3 {
      margin: 0 0 6px;
      color: #fff;
      font-size: 1.08rem;
    }
    .risk-panel-intro p {
      margin: 0;
      color: #999;
      font-size: 0.88rem;
      line-height: 1.6;
    }
    .risk-mail-wrap { position: relative; flex: 0 0 auto; }
    .risk-mail-button {
      position: relative;
      width: 44px;
      height: 44px;
      border: 1px solid #454545;
      border-radius: 12px;
      background: #181818;
      color: #eee;
      cursor: pointer;
      font-size: 1.05rem;
    }
    .risk-mail-button:hover { border-color: #64ffda; color: #64ffda; }
    .risk-mail-badge {
      position: absolute;
      top: -7px;
      right: -7px;
      min-width: 20px;
      height: 20px;
      padding: 0 5px;
      border-radius: 999px;
      background: #ff5d73;
      color: #fff;
      border: 2px solid #181818;
      font-size: 0.72rem;
      font-weight: 700;
      line-height: 16px;
      text-align: center;
      display: none;
    }
    .risk-mail-panel {
      display: none;
      position: absolute;
      top: 52px;
      right: 0;
      z-index: 10020;
      width: min(380px, calc(100vw - 56px));
      max-height: 430px;
      overflow: hidden;
      background: #151515;
      border: 1px solid #444;
      border-radius: 14px;
      box-shadow: 0 18px 50px rgba(0,0,0,0.45);
    }
    .risk-mail-panel.open { display: block; }
    .risk-mail-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      padding: 14px 15px;
      border-bottom: 1px solid #333;
    }
    .risk-field select { width:100%;padding:12px;background:#161616;color:#fff;border:1px solid #444;border-radius:8px;font:inherit; }
    .risk-mail-head strong { color: #fff; }
    .risk-mail-actions { display: flex; gap: 6px; }
    .risk-mail-mini-btn {
      border: 1px solid #444;
      background: transparent;
      color: #aaa;
      border-radius: 7px;
      padding: 5px 8px;
      cursor: pointer;
      font-size: 0.76rem;
    }
    .risk-mail-mini-btn:hover { color: #64ffda; border-color: #64ffda; }
    .risk-mail-list { max-height: 360px; overflow-y: auto; }
    .risk-mail-empty { padding: 28px 18px; text-align: center; color: #777; }
    .risk-mail-item {
      padding: 13px 15px;
      border-bottom: 1px solid #292929;
      cursor: pointer;
    }
    .risk-mail-item:last-child { border-bottom: 0; }
    .risk-mail-item.unread { background: rgba(100,255,218,0.065); }
    .risk-mail-item-title { color: #f2f2f2; font-weight: 700; margin-bottom: 5px; }
    .risk-mail-item-message { color: #bbb; font-size: 0.86rem; line-height: 1.5; }
    .risk-mail-item-time { color: #707070; font-size: 0.74rem; margin-top: 6px; }

    .risk-form-layout {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 18px;
      margin-bottom: 16px;
    }
    .risk-form-section {
      background: rgba(0,0,0,0.18);
      border: 1px solid #303030;
      border-radius: 12px;
      padding: 18px;
    }
    .risk-form-section-title {
      display: flex;
      align-items: center;
      gap: 8px;
      margin: 0 0 15px;
      color: #e8e8e8;
      font-size: 0.98rem;
    }
    .risk-form-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 13px;
    }
    .risk-field label {
      display: block;
      color: #aaa;
      font-size: 0.86rem;
      margin-bottom: 6px;
    }
    .risk-field input {
      box-sizing: border-box;
      width: 100%;
      padding: 10px 11px;
      border-radius: 7px;
      border: 1px solid #444;
      background: #161616;
      color: #fff;
      outline: none;
    }
    .risk-field input:focus {
      border-color: #64ffda;
      box-shadow: 0 0 0 2px rgba(100,255,218,0.12);
    }
    .risk-field input[readonly] { color: #64ffda; background: #111d1a; }
    .risk-field-note {
      display: block;
      color: #737373;
      font-size: 0.74rem;
      line-height: 1.4;
      margin-top: 5px;
    }
    .risk-percent-input { position: relative; }
    .risk-percent-input input { padding-right: 34px; }
    .risk-percent-input span {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: #888;
      pointer-events: none;
    }
    .risk-advanced {
      margin-top: 14px;
      border-top: 1px solid #303030;
      padding-top: 12px;
    }
    .risk-advanced summary {
      color: #999;
      cursor: pointer;
      font-size: 0.86rem;
      user-select: none;
    }
    .risk-advanced .risk-form-grid { margin-top: 13px; }
    .risk-actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      align-items: center;
      margin: 4px 0 12px;
    }
    .risk-btn {
      padding: 9px 16px;
      border: none;
      border-radius: 7px;
      cursor: pointer;
      font-weight: bold;
    }
    .risk-btn-primary { background: #64ffda; color: #111; }
    .risk-btn-secondary { background: #333; color: #eee; border: 1px solid #555; }
    .risk-btn-danger { background: transparent; color: #ff6b6b; border: 1px solid #ff6b6b; }
    .risk-tip { color: #888; font-size: 0.82rem; line-height: 1.5; }
    .risk-alert-message {
      display: none;
      margin: 10px 0 15px;
      padding: 10px 12px;
      border-radius: 8px;
      font-size: 0.95rem;
    }
    .risk-alert-message.ok { display: block; background: rgba(100,255,218,0.12); border: 1px solid rgba(100,255,218,0.5); color: #64ffda; }
    .risk-alert-message.warn { display: block; background: rgba(255,107,107,0.12); border: 1px solid rgba(255,107,107,0.5); color: #ff8a8a; }

    .risk-saved-block {
      margin-top: 20px;
      padding-top: 18px;
      border-top: 1px solid #333;
    }
    .risk-saved-title {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 12px;
    }
    .risk-saved-title h4 { margin: 0; color: #eee; }
    .risk-saved-title span { color: #777; font-size: 0.8rem; }
    .risk-stock-list {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
      gap: 10px;
    }
    .risk-stock-card {
      display: flex;
      align-items: center;
      gap: 9px;
      width: 100%;
      padding: 12px 13px;
      border: 1px solid #3b3b3b;
      border-radius: 10px;
      background: #191919;
      color: #eee;
      cursor: pointer;
      text-align: left;
      transition: border-color .2s ease, transform .2s ease, background .2s ease;
    }
    .risk-stock-card:hover {
      border-color: #64ffda;
      background: rgba(100,255,218,0.06);
      transform: translateY(-1px);
    }
    .risk-stock-code { color: #64ffda; font-weight: 800; }
    .risk-stock-name { color: #ddd; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .risk-empty-row { color: #777; text-align: center; padding: 18px; border: 1px dashed #393939; border-radius: 10px; }

    .risk-detail-overlay {
      display: none;
      position: fixed;
      inset: 0;
      z-index: 10030;
      padding: 20px;
      background: rgba(0,0,0,0.72);
      align-items: center;
      justify-content: center;
    }
    .risk-detail-overlay.open { display: flex; }
    .risk-detail-card {
      width: min(620px, 100%);
      max-height: calc(100vh - 40px);
      overflow-y: auto;
      background: #171717;
      border: 1px solid #444;
      border-radius: 15px;
      box-shadow: 0 24px 70px rgba(0,0,0,0.55);
    }
    .risk-detail-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 17px 19px;
      border-bottom: 1px solid #333;
    }
    .risk-detail-head h3 { margin: 0; color: #fff; }
    .risk-detail-close {
      border: 0;
      background: transparent;
      color: #aaa;
      font-size: 1.5rem;
      cursor: pointer;
    }
    .risk-detail-body { padding: 18px 19px; }
    .risk-detail-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0,1fr));
      gap: 10px;
    }
    .risk-detail-item {
      padding: 11px 12px;
      border: 1px solid #303030;
      border-radius: 9px;
      background: #121212;
    }
    .risk-detail-item span { display: block; color: #777; font-size: 0.76rem; margin-bottom: 5px; }
    .risk-detail-item strong { color: #eee; font-size: 0.94rem; word-break: break-word; }
    .risk-detail-footer {
      display: flex;
      justify-content: flex-end;
      gap: 9px;
      padding: 0 19px 19px;
      flex-wrap: wrap;
    }
    .risk-status-badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 999px;
      font-size: 0.8rem;
      font-weight: bold;
      white-space: nowrap;
    }
    .risk-status-watch { color: #64ffda; border: 1px solid #64ffda; background: rgba(100,255,218,0.08); }
    .risk-status-stop { color: #ff6b6b; border: 1px solid #ff6b6b; background: rgba(255,107,107,0.08); }
    .risk-status-profit { color: #ffd166; border: 1px solid #ffd166; background: rgba(255,209,102,0.08); }
    .risk-status-time { color: #b39ddb; border: 1px solid #b39ddb; background: rgba(179,157,219,0.08); }

    @media (max-width: 860px) {
      .risk-form-layout { grid-template-columns: 1fr; }
    }
    @media (max-width: 560px) {
      .risk-alert-panel { padding: 16px; margin-left: 12px; margin-right: 12px; }
      .risk-form-grid, .risk-detail-grid { grid-template-columns: 1fr; }
      .risk-panel-topbar { align-items: center; }
      .risk-stock-list { grid-template-columns: 1fr; }
    }
  </style>
<?php require __DIR__ . '/includes/site-head.php'; ?>
</head>
<body class="site-layout">
<?php require __DIR__ . '/includes/site-header.php'; ?>

  

  <section class="hero-visual" aria-label="古估股首頁主視覺">
    <video autoplay muted loop playsinline preload="metadata" poster="assets/hero-video-poster.jpg" aria-label="古估股首頁主視覺影片">
      <source src="assets/hero-visual.mp4" type="video/mp4">
      您的瀏覽器不支援影片播放。
    </video>
  </section>

  <nav class="home-tool-nav" aria-label="首頁功能快速入口">
    <a href="#marketChart"><span class="home-tool-icon">📈</span><span><strong>即時行情</strong><small>個股與 ETF K 線</small></span></a>
    <a href="#riskAlertSection"><span class="home-tool-icon">🎯</span><span><strong>風險提醒</strong><small>停損與停利設定</small></span></a>
    <a href="#etfSection"><span class="home-tool-icon">🧭</span><span><strong>市場概況</strong><small>ETF 與產業情緒</small></span></a>
    <a href="#utilityTools"><span class="home-tool-icon">🧰</span><span><strong>實用工具</strong><small>購買力與旅行社查詢</small></span></a>
  </nav>

  <section id="marketChart" class="topic-section dashboard-feature dashboard-feature-chart">
    <div class="section-header">
      <div>
        <span class="section-eyebrow">MARKET CHART</span>
        <h2>📈 即時個股 / ETF K 線圖</h2>
        <p class="section-description">輸入台股代號，即可查看目前走勢與價格變化。</p>
      </div>
    </div>
    <div class="chart-toolbar">
      <label for="klineSymbol">股票代號</label>
      <div class="chart-search-control">
        <input type="text" id="klineSymbol" placeholder="例如：2330">
        <button onclick="loadKline()"><i class="fas fa-chart-line"></i> 查詢行情</button>
      </div>
    </div>
    <div id="tv_chart_container"></div>
  </section>

  <section id="riskAlertSection" class="topic-section">
    <div class="section-header">
      <div>
        <span class="section-eyebrow">RISK CONTROL</span>
        <h2>🎯 停損 / 獲利了結提醒</h2>
        <p class="section-description">先建立規則，讓投資決策不被當下情緒帶著走。</p>
      </div>
      <button type="button" class="section-toggle" aria-expanded="false" aria-controls="riskAlertPanel" onclick="toggleRiskSection(this)">
        <span>展開設定</span><i class="fas fa-chevron-down"></i>
      </button>
    </div>

    <div id="riskAlertPanel" class="risk-alert-panel" hidden>
      <div class="risk-panel-topbar">
        <div class="risk-panel-intro">
          <h3>建立股票風險提醒</h3>
          <p>輸入買進價後會自動試算停損與停利建議，也可以自行調整百分比或實際觸發價格。</p>
        </div>
        <div class="risk-mail-wrap">
          <button type="button" id="riskMailButton" class="risk-mail-button" aria-label="開啟提醒信箱" onclick="toggleRiskInbox(event)">
            <i class="fas fa-envelope"></i>
            <span id="riskMailBadge" class="risk-mail-badge">0</span>
          </button>
          <div id="riskMailPanel" class="risk-mail-panel">
            <div class="risk-mail-head">
              <strong><i class="fas fa-inbox"></i> 提醒信箱</strong>
              <div class="risk-mail-actions">
                <button type="button" class="risk-mail-mini-btn" onclick="markAllRiskNotificationsRead(event)">全部已讀</button>
                <button type="button" class="risk-mail-mini-btn" onclick="clearRiskNotifications(event)">清空</button>
              </div>
            </div>
            <div id="riskMailList" class="risk-mail-list"></div>
          </div>
        </div>
      </div>

      
      <div class="risk-form-section" style="margin:16px 0;">
        <h4 class="risk-form-section-title">通知信箱設定</h4>
        <div class="risk-form-grid">
          <div class="risk-field"><label for="notificationEmail">通知 Email</label><input type="email" id="notificationEmail" required placeholder="輸入你的收件信箱" autocomplete="email"></div>
          <div class="risk-field" style="justify-content:end;"><button type="button" class="risk-btn risk-btn-primary" id="verifyEmailButton">寄送驗證信</button></div>
        </div>
        <p id="notificationEmailStatus" role="status" style="margin-top:12px;color:#aeb9d8;">正在讀取通知設定…</p>
        <p class="risk-tip">提醒由伺服器監控；關閉瀏覽器仍可通知，但本機 Node.js 與 MySQL 必須持續運行。價格提醒只採近期有效 MIS 報價。</p>
        <button type="button" class="risk-btn risk-btn-secondary" id="importOldRiskRules" hidden>匯入此瀏覽器的舊提醒</button>
      </div>


      <div class="risk-form-layout">
        <div class="risk-form-section">
          <h4 class="risk-form-section-title"><i class="fas fa-chart-line"></i> 基本資料</h4>
          <div class="risk-form-grid">
            <div class="risk-field">
              <label for="riskSymbol">股票代號</label>
              <input type="text" id="riskSymbol" placeholder="如：2330" autocomplete="off">
            </div>
            <div class="risk-field"><label for="riskMarket">股票市場</label><select id="riskMarket"><option value="tse">上市</option><option value="otc">上櫃</option></select></div>
            <div class="risk-field">
              <label for="riskName">股票名稱</label>
              <input type="text" id="riskName" placeholder="可留空，自動帶入">
            </div>
            <div class="risk-field">
              <label for="riskBuyPrice">買進價</label>
              <input type="number" id="riskBuyPrice" placeholder="如：580" step="0.01">
            </div>
            <div class="risk-field">
              <label for="riskCurrentPrice">目前價</label>
              <input type="number" id="riskCurrentPrice" placeholder="依 MIS 近期有效報價自動更新" step="0.01" readonly>
            </div>
          </div>
        </div>

        <div class="risk-form-section">
          <h4 class="risk-form-section-title"><i class="fas fa-sliders-h"></i> 停損與停利設定</h4>
          <div class="risk-form-grid">
            <div class="risk-field">
              <label for="riskStopPercent">建議停損幅度</label>
              <div class="risk-percent-input">
                <input type="number" id="riskStopPercent" value="5" min="0.1" max="99" step="0.1">
                <span>%</span>
              </div>
              <small class="risk-field-note">預設買進價下跌 5%，可手動調整。</small>
            </div>
            <div class="risk-field">
              <label for="riskTakePercent">建議獲利幅度</label>
              <div class="risk-percent-input">
                <input type="number" id="riskTakePercent" value="10" min="0.1" max="999" step="0.1">
                <span>%</span>
              </div>
              <small class="risk-field-note">預設買進價上漲 10%，可手動調整。</small>
            </div>
            <div class="risk-field">
              <label for="riskSuggestedStopPrice">自動建議停損價</label>
              <input type="number" id="riskSuggestedStopPrice" placeholder="依買進價自動計算" step="0.01" readonly>
            </div>
            <div class="risk-field">
              <label for="riskSuggestedTakePrice">自動建議獲利價</label>
              <input type="number" id="riskSuggestedTakePrice" placeholder="依買進價自動計算" step="0.01" readonly>
            </div>
            <div class="risk-field">
              <label for="riskStopPrice">實際停損價</label>
              <input type="number" id="riskStopPrice" placeholder="預設套用建議值" step="0.01">
            </div>
            <div class="risk-field">
              <label for="riskTakePrice">實際獲利了結價</label>
              <input type="number" id="riskTakePrice" placeholder="預設套用建議值" step="0.01">
            </div>
          </div>

          <details class="risk-advanced">
            <summary>進階設定：點數與提醒期限</summary>
            <div class="risk-form-grid">
              <div class="risk-field">
                <label for="riskStopPoints">停損點數</label>
                <input type="number" id="riskStopPoints" placeholder="買進價往下幾點" step="0.01">
              </div>
              <div class="risk-field">
                <label for="riskTakePoints">停利點數</label>
                <input type="number" id="riskTakePoints" placeholder="買進價往上幾點" step="0.01">
              </div>
              <div class="risk-field" style="grid-column:1/-1;">
                <label for="riskDeadline">提醒期限時間</label>
                <input type="hidden" id="riskDeadline">
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                  <input type="date" id="riskDeadlineDate" aria-label="提醒日期" style="flex:2;min-width:160px;color-scheme:dark">
                  <select id="riskDeadlineHour" aria-label="提醒小時" style="flex:1"><option value="">時</option><?php for ($h=0;$h<24;$h++): $hh=sprintf('%02d',$h); ?><option value="<?= $hh ?>"><?= $hh ?></option><?php endfor; ?></select>
                  <span>：</span>
                  <select id="riskDeadlineMinute" aria-label="提醒分鐘" style="flex:1"><option value="">分</option><?php for ($m=0;$m<60;$m++): $mm=sprintf('%02d',$m); ?><option value="<?= $mm ?>"><?= $mm ?></option><?php endfor; ?></select>
                </div>
                <small>台灣時間，24 小時制（01:30 為凌晨，13:30 為下午）。</small>
              </div>
            </div>
          </details>
        </div>
      </div>

      <p style="margin:16px 0;"><label><input type="checkbox" id="riskEmailEnabled" disabled> 此規則啟用 Email 提醒（需驗證通知信箱）</label></p>
      <div class="risk-actions">
        <div id="riskAlertMessage" class="risk-alert-message" role="status" aria-live="polite" style="width:100%"></div>
        <button type="button" id="riskSaveButton" class="risk-btn risk-btn-primary" onclick="addRiskRule()">新增停損 / 停利設定</button>
        <button type="button" class="risk-btn risk-btn-secondary" onclick="clearRiskForm()">清空表單</button>
        <span class="risk-tip">調整百分比時，系統會更新建議價格；若尚未手動修改實際價格，也會同步套用新建議。</span>
      </div>

      <div class="risk-saved-block">
        <div class="risk-saved-title">
          <h4>已新增股票</h4>
          <span>點擊股票代碼或名稱查看完整設定</span>
        </div>
        <div id="riskRuleList" class="risk-stock-list">
          <div class="risk-empty-row">尚未設定停損 / 獲利了結提醒</div>
        </div>
      </div>
    </div>
  </section>

  <div id="riskDetailOverlay" class="risk-detail-overlay" onclick="closeRiskDetail(event)">
    <div class="risk-detail-card" role="dialog" aria-modal="true" aria-labelledby="riskDetailTitle" onclick="event.stopPropagation()">
      <div class="risk-detail-head">
        <h3 id="riskDetailTitle">股票提醒詳細資料</h3>
        <button type="button" class="risk-detail-close" aria-label="關閉" onclick="closeRiskDetail()">&times;</button>
      </div>
      <div class="risk-detail-body">
        <div id="riskDetailGrid" class="risk-detail-grid"></div>
      </div>
      <div class="risk-detail-footer">
        <button type="button" class="risk-btn risk-btn-secondary" onclick="editSelectedRiskRule()">載入上方修改</button>
        <button type="button" class="risk-btn risk-btn-danger" onclick="deleteSelectedRiskRule()">刪除設定</button>
        <button type="button" class="risk-btn risk-btn-primary" onclick="closeRiskDetail()">完成</button>
      </div>
    </div>
  </div>


  <section id="etfSection" aria-label="觀光類的 ETF 狀況">
    <h2>觀光產業相關ETF</h2>
    <div id="update-time">初始化中...</div>
    <div id="etfTicker" role="list"></div>
  </section>

  <section id="sector-heatmap" class="topic-section">
      <div class="section-header">
          <h2>今日市場情緒溫度計 🔥</h2>
      </div>
      <div class="heatmap-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
          
          <div class="heatmap-card" id="heat-food" style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; text-align: center; border: 1px solid #333;">
              <div style="font-size: 1.1rem; color: #aaa;">美食餐飲</div>
              <div class="temp-gauge-wrapper">
                  <div class="temp-bar" id="bar-food"></div> </div>
              <div class="heat-value" style="font-size: 2rem; font-weight: bold; margin: 10px 0;">-- %</div>
              <div class="heat-status" style="font-size: 0.9rem;">計算中...</div>
          </div>

          <div class="heatmap-card" id="heat-travel" style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; text-align: center; border: 1px solid #333;">
              <div style="font-size: 1.1rem; color: #aaa;">旅遊觀光</div>
              <div class="temp-gauge-wrapper">
                  <div class="temp-bar" id="bar-travel"></div> </div>
              <div class="heat-value" style="font-size: 2rem; font-weight: bold; margin: 10px 0;">-- %</div>
              <div class="heat-status" style="font-size: 0.9rem;">計算中...</div>
          </div>

          <div class="heatmap-card" id="heat-aviation" style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; text-align: center; border: 1px solid #333;">
              <div style="font-size: 1.1rem; color: #aaa;">航空運輸</div>
              <div class="temp-gauge-wrapper">
                  <div class="temp-bar" id="bar-aviation"></div> </div>
              <div class="heat-value" style="font-size: 2rem; font-weight: bold; margin: 10px 0;">-- %</div>
              <div class="heat-status" style="font-size: 0.9rem;">計算中...</div>
          </div>
      </div>
  </section>

  <section id="utilityTools" class="utility-tools-section" aria-labelledby="utilityToolsTitle">
    <div class="utility-tools-heading">
      <span class="section-eyebrow">SMART UTILITIES</span>
      <h2 id="utilityToolsTitle">日常實用工具</h2>
      <p class="section-description">把常用的小工具集中在同一區，不必在長頁面中來回尋找。</p>
    </div>
    <div class="utility-tools-grid">
      <div class="calculator-section">
      <h3 class="calc-title"><i class="fas fa-calculator"></i> GUGU 購買力轉換器</h3>
      <p style="color: #ccc; margin-bottom: 20px;">輸入股票代碼，自動抓取網頁上的即時股價！</p>
      
      <div class="calc-flex-container">
          <div class="calc-input-group">
              <label>股票代號 (如: 2727)</label>
              <input type="text" id="calcStockSymbol" class="calc-input" placeholder="輸入代碼" oninput="autoFetchPrice()">
              <div id="stockNameDisplay"></div>
          </div>

          <div class="calc-input-group">
              <label>目前股價 (元/股)</label>
              <input type="number" id="calcStockPrice" class="calc-input" placeholder="自動帶入..." readonly>
          </div>

          <div class="calc-input-group">
              <label>持有張數</label>
              <input type="number" id="calcStockAmount" class="calc-input" placeholder="1" value="1">
          </div>

          <div class="calc-input-group">
              <label>想換算成...</label>
              <select id="calcCompareItem" class="calc-select">
                  <option value="60">🥤 珍珠奶茶 ($60)</option>
                  <option value="90">🍗 大雞排 ($90)</option>
                  <option value="15000">✈️ 日本來回機票 ($15,000)</option>
                  <option value="30000">📱 iPhone 新機 ($30,000)</option>
                  <option value="3000000">🏎️ 保時捷 ($3,000,000)</option>
              </select>
          </div>
          
      </div>

      <button class="calc-btn" onclick="runCalculator()">開始換算</button>

      <div id="calcResultDisplay" class="calc-result-box">
          </div>
      </div>

      <div class="cyberpunk-search-box">
    <div class="search-title">
        <span class="search-icon">🔍</span> 合法旅行社查詢 [政府資料同步]
    </div>
    <div class="input-group">
        <input type="text" id="searchInput" class="cyber-input" placeholder="請輸入旅行社名稱（例如：雄獅、東晟...）" autocomplete="off">
        <button id="searchBtn" class="cyber-btn">查詢</button>
    </div>
    <div id="searchResultArea" class="cyber-results"></div>
    <div class="status-text">
        <span id="dbStatus" class="status-success">⏳ 正在連線至資料庫...</span>
    </div>
      </div>
    </div>
  </section>

  <section id="food" class="topic-section">
    <div class="section-header">
      <h2>美食區🍽️ <small style="font-size:0.6em; color:#aaa;">(點擊股票可查看持有之 ETF)</small></h2>
      <a href="lists/food-list.php" class="btn-more" aria-label="查看更多美食類股票">查看更多 <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="stock-table-container">
      <table class="stock-table">
        <thead><tr><th>代號</th><th>名稱</th><th>股價</th><th>漲跌</th><th>漲跌幅</th><th>成交量</th><th>收藏</th></tr></thead>
        <tbody id="food-stock-list"></tbody>
      </table>
      <div class="update-info"><small>資料更新時間: <span class="update-time">載入中...</span></small></div>
    </div>
  </section>

  <section id="travel" class="topic-section">
    <div class="section-header">
      <h2>旅遊區🧳 <small style="font-size:0.6em; color:#aaa;">(點擊股票可查看持有之 ETF)</small></h2>
      <a href="lists/travel-list.php" class="btn-more" aria-label="查看更多旅遊類股票">查看更多 <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="stock-table-container">
      <table class="stock-table">
        <thead><tr><th>代號</th><th>名稱</th><th>股價</th><th>漲跌</th><th>漲跌幅</th><th>成交量</th></tr></thead>
        <tbody id="travel-stock-list"></tbody>
      </table>
      <div class="update-info"><small>資料更新時間: <span class="update-time">載入中...</span></small></div>
    </div>
  </section>

  <section id="aviation" class="topic-section">
    <div class="section-header">
      <h2>航空區✈️ <small style="font-size:0.6em; color:#aaa;">(點擊股票可查看持有之 ETF)</small></h2>
      <a href="lists/aviation-list.php" class="btn-more" aria-label="查看更多航空類股票">查看更多 <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="stock-table-container">
      <table class="stock-table">
        <thead><tr><th>代號</th><th>名稱</th><th>股價</th><th>漲跌</th><th>幅度</th><th>成交量</th></tr></thead>
        <tbody id="aviation-stock-list"></tbody>
      </table>
      <div class="update-info"><small>資料更新時間: <span class="update-time">載入中...</span></small></div>
    </div>
  </section>

  <div id="costCalculatorModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
      <button id="modalCloseBtn" class="modal-close">&times;</button>
      <h3 id="modalTitle">交易成本試算</h3>
      <div class="modal-body">
        <div class="input-group">
          <label for="modalAmount">交易金額 (元)</label>
          <input type="number" id="modalAmount" value="50000" step="1000">
        </div>
        <div class="input-group">
          <label for="modalDiscount">您的券商折扣 (折)</label>
          <input type="number" id="modalDiscount" value="2.8" step="0.1">
        </div>
        <div class="input-group">
          <label for="modalMgmtFee">預估年管理費 (%)</label>
          <input type="number" id="modalMgmtFee" value="0.3" step="0.01">
        </div>
        <div class="results-group">
          <h4>試算結果：</h4>
          <p>買進成本：<span id="buyCostResult"></span></p>
          <p>賣出成本：<span id="sellCostResult"></span></p>
          <p>預估年持有：<span id="mgmtFeeResult"></span></p>
          <hr>
          <p>一買一賣總交易：<span id="totalCostResult"></span></p>
        </div>
      </div>
    </div>
  </div>

  <div id="etfDetailModal" class="modal-overlay" style="display: none; z-index: 9999;">
    <div class="modal-content">
      <button class="modal-close" onclick="document.getElementById('etfDetailModal').style.display='none'">&times;</button>
      <h3 id="etfModalTitle">ETF 持股分析</h3>
      <div class="modal-body">
        <div class="stock-table-container">
          <table class="stock-table">
            <thead><tr><th>股票名稱</th><th>持有張數</th><th>權重佔比</th></tr></thead>
            <tbody id="etfHoldingList"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div id="stockReverseModal" class="modal-overlay" style="display: none; z-index: 9999;">
    <div class="modal-content">
      <button class="modal-close" onclick="document.getElementById('stockReverseModal').style.display='none'">&times;</button>
      <h3 id="reverseModalTitle">持股 ETF 查詢</h3>
      <div class="modal-body">
        <p style="color: #64ffda; margin-bottom: 15px; font-size: 1.1em;">
            <i class="fas fa-search-dollar"></i> 
            股票 <strong id="currentStockName" style="color: white; font-size:1.2em;"></strong> 被以下 ETF 所持有：
        </p>
        <div class="stock-table-container">
          <table class="stock-table">
            <thead><tr><th>ETF 代號</th><th>ETF 名稱</th><th>持有佔比</th><th>相關性</th></tr></thead>
            <tbody id="reverseEtfList"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <link rel="stylesheet" href="styles/mascot-drag.css?v=<?= filemtime(__DIR__ . '/styles/mascot-drag.css') ?>">
  <div id="mascot-helper" title="拖曳可移動，輕點開啟漫畫教室" style="z-index: 10000; cursor: pointer; right: 20px; left: auto; bottom: 20px; position: fixed;">
    <img src="assets/GUGU.png" alt="網站吉祥物 GUGU">
    <div class="chat-bubble"></div>
  </div>



  <script src="scripts/script.js?v=<?= filemtime(__DIR__ . '/scripts/script.js') ?>"></script>
  <script src="scripts/gugu.js"></script>
  <script src="scripts/mascot-drag.js?v=<?= filemtime(__DIR__ . '/scripts/mascot-drag.js') ?>"></script>
  <script src="scripts/main-page.js?v=<?= filemtime(__DIR__ . '/scripts/main-page.js') ?>"></script>

<script>
// 將首頁已快取的市場資料提供給停損 / 停利提醒功能使用
window.GUGU_MARKET_SNAPSHOT = <?php echo json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>

<script>
const GUGU_RISK_STORAGE_KEY = 'guguStopProfitRules';
let guguRiskRules = [];
let guguRiskNotifications = [];
let guguPriceMap = {};
let selectedRiskRuleUid = null;
let editingRiskRuleUid = null;
let riskPolling = false;

function guguToNumber(value) {
  if (value === null || value === undefined) return null;
  const cleaned = String(value).replace(/,/g, '').replace(/%/g, '').trim();
  if (cleaned === '' || cleaned === '-' || cleaned === '--') return null;
  const num = Number(cleaned);
  return Number.isFinite(num) ? num : null;
}

function escapeRiskHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function normalizeRiskRule(rule) {
  const stopPercent = guguToNumber(rule.stopPercent) ?? 5;
  const takePercent = guguToNumber(rule.takePercent) ?? 10;
  const suggested = calculateSuggestedRiskPrices(rule.buyPrice, stopPercent, takePercent);

  return {
    ...rule,
    uid: String(rule.uid || Date.now()),
    symbol: String(rule.symbol || '').trim().toUpperCase(),
    stopPercent,
    takePercent,
    suggestedStopPrice: guguToNumber(rule.suggestedStopPrice) ?? suggested.suggestedStopPrice,
    suggestedTakePrice: guguToNumber(rule.suggestedTakePrice) ?? suggested.suggestedTakePrice,
    stopPoints: guguToNumber(rule.stopPoints),
    takePoints: guguToNumber(rule.takePoints)
  };
}

async function riskApi(path, method = 'GET', body) {
  const options = { method, credentials: 'include', cache: 'no-store', headers: {} };
  if (method !== 'GET') {
    options.headers['X-CSRF-Token'] = await getCsrfToken();
    if (body !== undefined) { options.headers['Content-Type'] = 'application/json'; options.body = JSON.stringify(body); }
  }
  const response = await fetch(`${PROXY_URL}/api/${path}`, options);
  const result = await response.json();
  if (!response.ok || !result.success) throw new Error(result.message || '提醒服務暫時無法使用');
  return result;
}
async function loadRiskRules() {
  guguRiskRules = (await riskApi('risk-rules')).rules.map(normalizeRiskRule);
}
async function loadRiskNotifications() {
  guguRiskNotifications = (await riskApi('risk-notifications')).notifications;
}
async function refreshRiskServer() {
  if (riskPolling) return;
  riskPolling = true;
  try {
    await Promise.all([loadRiskRules(), loadRiskNotifications()]);
    const data = await riskApi('risk-prices');
    guguPriceMap = Object.fromEntries(data.quotes.map(q => [q.id, { name:q.name, price:q.available ? q.price : null }]));
    renderRiskRules(); renderRiskInbox(); autofillRiskQuote();
  } catch (error) { console.warn('提醒更新失敗：',error.message); }
  finally { riskPolling = false; }
}
async function loadNotificationEmail() {
  const data = await riskApi('email-settings');
  const emailInput = document.getElementById('notificationEmail');
  if (document.activeElement !== emailInput) emailInput.value = data.email;
  document.getElementById('riskEmailEnabled').disabled = !data.verified || !data.emailAvailable;
  document.getElementById('verifyEmailButton').disabled = !data.emailAvailable;
  document.getElementById('notificationEmailStatus').textContent = !data.emailAvailable ? '寄信服務尚未啟用；可先建立站內提醒。' : data.verified ? '通知信箱已驗證，可啟用 Email 提醒。' : data.email ? `通知信箱尚未驗證。${data.lastSentAt ? '驗證信已於 ' + formatDeadline(data.lastSentAt) + ' 交給寄信服務，收件信箱：' + data.email + '。請查看垃圾郵件；未找到時可重新寄送。' : '請按「寄送驗證信」。'}` : '請填入通知信箱並寄送驗證信。';
}

function showRiskAlert(message, type = 'ok') {
  const box = document.getElementById('riskAlertMessage');
  if (!box) return;
  box.className = 'risk-alert-message ' + type;
  box.textContent = message;

}

function formatRiskNumber(value) {
  const num = guguToNumber(value);
  if (num === null) return '--';
  return Number.isInteger(num) ? String(num) : num.toFixed(2).replace(/\.00$/, '');
}

function formatDeadline(value) {
  if (!value) return '--';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '--';
  return date.toLocaleString('zh-TW', { hour12: false });
}

function calculateSuggestedRiskPrices(buyPrice, stopPercent = 5, takePercent = 10) {
  const buy = guguToNumber(buyPrice);
  const stopPct = guguToNumber(stopPercent);
  const takePct = guguToNumber(takePercent);

  if (buy === null || buy <= 0 || stopPct === null || takePct === null) {
    return { suggestedStopPrice: null, suggestedTakePrice: null };
  }

  return {
    suggestedStopPrice: Number((buy * (1 - stopPct / 100)).toFixed(2)),
    suggestedTakePrice: Number((buy * (1 + takePct / 100)).toFixed(2))
  };
}

function riskNumbersEqual(a, b) {
  const first = guguToNumber(a);
  const second = guguToNumber(b);
  if (first === null || second === null) return false;
  return Math.abs(first - second) < 0.005;
}

function updateSuggestedRiskPrices(autoApply = true) {
  const buyPrice = document.getElementById('riskBuyPrice')?.value;
  const stopPercent = document.getElementById('riskStopPercent')?.value;
  const takePercent = document.getElementById('riskTakePercent')?.value;
  const suggestedStopEl = document.getElementById('riskSuggestedStopPrice');
  const suggestedTakeEl = document.getElementById('riskSuggestedTakePrice');
  const stopPriceEl = document.getElementById('riskStopPrice');
  const takePriceEl = document.getElementById('riskTakePrice');

  const oldSuggestedStop = suggestedStopEl?.value;
  const oldSuggestedTake = suggestedTakeEl?.value;
  const suggested = calculateSuggestedRiskPrices(buyPrice, stopPercent, takePercent);

  if (suggestedStopEl) suggestedStopEl.value = suggested.suggestedStopPrice ?? '';
  if (suggestedTakeEl) suggestedTakeEl.value = suggested.suggestedTakePrice ?? '';

  if (autoApply && stopPriceEl && (!stopPriceEl.value || riskNumbersEqual(stopPriceEl.value, oldSuggestedStop))) {
    stopPriceEl.value = suggested.suggestedStopPrice ?? '';
  }
  if (autoApply && takePriceEl && (!takePriceEl.value || riskNumbersEqual(takePriceEl.value, oldSuggestedTake))) {
    takePriceEl.value = suggested.suggestedTakePrice ?? '';
  }
}

function getRiskStatus(rule, latestPrice) {
  const price = guguToNumber(latestPrice);
  const stop = guguToNumber(rule.stopPrice);
  const take = guguToNumber(rule.takePrice);
  const deadline = rule.deadline ? new Date(rule.deadline) : null;

  if (price !== null && price > 0 && stop !== null && price <= stop) {
    return { key: 'stop', text: '已觸發停損', cls: 'risk-status-stop' };
  }
  if (price !== null && price > 0 && take !== null && price >= take) {
    return { key: 'profit', text: '已達獲利', cls: 'risk-status-profit' };
  }
  if (deadline && !Number.isNaN(deadline.getTime()) && new Date() >= deadline) {
    return { key: 'time', text: '時間到期', cls: 'risk-status-time' };
  }
  return { key: 'watch', text: '追蹤中', cls: 'risk-status-watch' };
}

function calculateProfitPercent(rule, latestPrice) {
  const buy = guguToNumber(rule.buyPrice);
  const price = guguToNumber(latestPrice);
  if (buy === null || buy <= 0 || price === null) return '--';
  const pct = ((price - buy) / buy) * 100;
  const sign = pct > 0 ? '+' : '';
  return sign + pct.toFixed(2) + '%';
}

function getRuleLatestPrice(rule) {
  const quote = guguPriceMap[rule.symbol];
  return quote && quote.price > 0 ? quote.price : null;
}

function renderRiskRules() {
  const list = document.getElementById('riskRuleList');
  if (!list) return;

  if (!guguRiskRules.length) {
    list.innerHTML = '<div class="risk-empty-row">尚未設定停損 / 獲利了結提醒</div>';
    return;
  }

  list.innerHTML = guguRiskRules.map(rule => `
    <button type="button" class="risk-stock-card" onclick="openRiskDetail('${escapeRiskHtml(rule.uid)}')">
      <span class="risk-stock-code">${escapeRiskHtml(rule.symbol)}</span>
      <span class="risk-stock-name">${escapeRiskHtml(rule.name || '未命名股票')}</span>
    </button>
  `).join('');
}

function clearRiskForm() {
  ['riskSymbol','riskName','riskBuyPrice','riskCurrentPrice','riskSuggestedStopPrice','riskSuggestedTakePrice','riskStopPrice','riskTakePrice','riskStopPoints','riskTakePoints','riskDeadline','riskDeadlineDate','riskDeadlineHour','riskDeadlineMinute'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
  const stopPercentEl = document.getElementById('riskStopPercent');
  const takePercentEl = document.getElementById('riskTakePercent');
  if (stopPercentEl) stopPercentEl.value = '5';
  if (takePercentEl) takePercentEl.value = '10';
  selectedRiskRuleUid = null;
  editingRiskRuleUid = null;
  document.getElementById('riskEmailEnabled').checked = false;
  document.getElementById('riskMarket').value = 'tse';
}

function autofillRiskQuote() {
  const symbolEl = document.getElementById('riskSymbol');
  if (!symbolEl) return;
  const symbol = symbolEl.value.trim().toUpperCase();
  symbolEl.value = symbol;

  if (!editingRiskRuleUid) document.getElementById('riskMarket').value = ['2729','7708','1268','5701'].includes(symbol) ? 'otc' : 'tse';
  const quote = guguPriceMap[symbol];
  if (!quote) return;

  const nameEl = document.getElementById('riskName');
  const priceEl = document.getElementById('riskCurrentPrice');

  if (nameEl && !nameEl.value && quote.name) nameEl.value = quote.name;
  if (priceEl && quote.price !== null) priceEl.value = quote.price;
}

async function addRiskRule() {
  const symbol = (document.getElementById('riskSymbol')?.value || '').trim().toUpperCase().replace(/\.TW[O]?$/, '');
  if (!symbol) {
    showRiskAlert('請先輸入股票代號。', 'warn');
    return;
  }

  const quote = guguPriceMap[symbol] || {};
  const name = (document.getElementById('riskName')?.value || quote.name || '').trim();
  const buyPrice = guguToNumber(document.getElementById('riskBuyPrice')?.value);
  const currentPrice = guguToNumber(document.getElementById('riskCurrentPrice')?.value) ?? quote.price ?? null;
  const stopPercent = guguToNumber(document.getElementById('riskStopPercent')?.value) ?? 5;
  const takePercent = guguToNumber(document.getElementById('riskTakePercent')?.value) ?? 10;
  const suggested = calculateSuggestedRiskPrices(buyPrice, stopPercent, takePercent);
  const suggestedStopPrice = suggested.suggestedStopPrice;
  const suggestedTakePrice = suggested.suggestedTakePrice;
  let stopPrice = guguToNumber(document.getElementById('riskStopPrice')?.value);
  let takePrice = guguToNumber(document.getElementById('riskTakePrice')?.value);
  const stopPoints = guguToNumber(document.getElementById('riskStopPoints')?.value);
  const takePoints = guguToNumber(document.getElementById('riskTakePoints')?.value);
  const date = document.getElementById('riskDeadlineDate').value;
  const hour = document.getElementById('riskDeadlineHour').value;
  const minute = document.getElementById('riskDeadlineMinute').value;
  if ((date || hour || minute) && (!date || hour === '' || minute === '')) {
    showRiskAlert('請完整選擇提醒日期、小時與分鐘。', 'warn'); return;
  }
  const deadline = date ? `${date}T${hour}:${minute}:00+08:00` : '';
  if (deadline && (!Number.isFinite(Date.parse(deadline)) || Date.parse(deadline) <= Date.now())) {
    showRiskAlert('提醒期限必須晚於現在，請設定往後 2～3 分鐘。', 'warn'); return;
  }

  if (buyPrice !== null && stopPrice === null && stopPoints !== null) {
    stopPrice = Math.max(0, buyPrice - stopPoints);
  }
  if (buyPrice !== null && takePrice === null && takePoints !== null) {
    takePrice = buyPrice + takePoints;
  }
  if (stopPrice === null) stopPrice = suggestedStopPrice;
  if (takePrice === null) takePrice = suggestedTakePrice;

  if (stopPrice === null && takePrice === null && !deadline) {
    showRiskAlert('請輸入買進價，或至少設定停損價、獲利價、提醒期限其中一項。', 'warn');
    return;
  }

  if (buyPrice !== null && stopPrice !== null && stopPrice >= buyPrice) {
    showRiskAlert('停損價通常要低於買進價，請再確認。', 'warn');
    return;
  }

  if (buyPrice !== null && takePrice !== null && takePrice <= buyPrice) {
    showRiskAlert('獲利了結價通常要高於買進價，請再確認。', 'warn');
    return;
  }

  const existingIndex = editingRiskRuleUid
    ? guguRiskRules.findIndex(rule => rule.uid === editingRiskRuleUid)
    : guguRiskRules.findIndex(rule => rule.symbol === symbol);
  const oldRule = existingIndex >= 0 ? guguRiskRules[existingIndex] : null;
  const newRule = {
    uid: oldRule?.uid || String(Date.now()),
    symbol,
    market: document.getElementById('riskMarket').value,
    emailEnabled: document.getElementById('riskEmailEnabled').checked,
    name,
    buyPrice,
    currentPrice,
    stopPercent,
    takePercent,
    suggestedStopPrice,
    suggestedTakePrice,
    stopPrice,
    takePrice,
    stopPoints,
    takePoints,
    deadline,
    createdAt: oldRule?.createdAt || new Date().toISOString(),
    updatedAt: new Date().toISOString()
  };

  const saveButton = document.getElementById('riskSaveButton');
  saveButton.disabled = true; showRiskAlert('正在儲存提醒…');
  try {
    const result = await riskApi('risk-rules', 'POST', { ...newRule, deadline: deadline ? new Date(deadline).toISOString() : '' });
    guguRiskRules = result.rules.map(normalizeRiskRule);
    renderRiskRules(); clearRiskForm();
    showRiskAlert('提醒已儲存至伺服器；達到條件時會建立通知。');
  } catch (error) { showRiskAlert(error.message, 'warn'); }
  finally { saveButton.disabled = false; }
}

function openRiskDetail(uid) {
  const rule = guguRiskRules.find(item => item.uid === uid);
  if (!rule) return;
  selectedRiskRuleUid = uid;

  const latestPrice = getRuleLatestPrice(rule);
  const status = getRiskStatus(rule, latestPrice);
  const profitPct = calculateProfitPercent(rule, latestPrice);
  const detailTitle = document.getElementById('riskDetailTitle');
  const detailGrid = document.getElementById('riskDetailGrid');
  const overlay = document.getElementById('riskDetailOverlay');

  if (detailTitle) detailTitle.textContent = `${rule.symbol} ${rule.name || ''}`.trim();
  if (detailGrid) {
    const details = [
      ['股票代號', rule.symbol],
      ['股票名稱', rule.name || '--'],
      ['買進價', formatRiskNumber(rule.buyPrice)],
      ['目前價', formatRiskNumber(latestPrice)],
      ['建議停損幅度', `${formatRiskNumber(rule.stopPercent)}%`],
      ['建議獲利幅度', `${formatRiskNumber(rule.takePercent)}%`],
      ['自動建議停損價', formatRiskNumber(rule.suggestedStopPrice)],
      ['自動建議獲利價', formatRiskNumber(rule.suggestedTakePrice)],
      ['實際停損價', formatRiskNumber(rule.stopPrice)],
      ['實際獲利價', formatRiskNumber(rule.takePrice)],
      ['停損點數', formatRiskNumber(rule.stopPoints)],
      ['停利點數', formatRiskNumber(rule.takePoints)],
      ['股票市場', rule.market === 'otc' ? '上櫃' : '上市'],
      ['通知方式', rule.emailEnabled ? '站內通知與 Email' : '站內通知'],
      ['提醒期限', formatDeadline(rule.deadline)],
      ['目前損益', profitPct],
      ['提醒狀態', status.text]
    ];

    detailGrid.innerHTML = details.map(([label, value]) => `
      <div class="risk-detail-item">
        <span>${escapeRiskHtml(label)}</span>
        <strong>${escapeRiskHtml(value)}</strong>
      </div>
    `).join('');
  }
  if (overlay) overlay.classList.add('open');
}

function closeRiskDetail(event) {
  if (event && event.target !== event.currentTarget) return;
  const overlay = document.getElementById('riskDetailOverlay');
  if (overlay) overlay.classList.remove('open');
}

function editSelectedRiskRule() {
  const rule = guguRiskRules.find(item => item.uid === selectedRiskRuleUid);
  if (!rule) return;
  editingRiskRuleUid = rule.uid;
  document.getElementById('riskMarket').value = rule.market || 'tse';
  document.getElementById('riskEmailEnabled').checked = Boolean(rule.emailEnabled);

  const values = {
    riskSymbol: rule.symbol,
    riskName: rule.name,
    riskBuyPrice: rule.buyPrice,
    riskCurrentPrice: getRuleLatestPrice(rule),
    riskStopPercent: rule.stopPercent,
    riskTakePercent: rule.takePercent,
    riskSuggestedStopPrice: rule.suggestedStopPrice,
    riskSuggestedTakePrice: rule.suggestedTakePrice,
    riskStopPrice: rule.stopPrice,
    riskTakePrice: rule.takePrice,
    riskStopPoints: rule.stopPoints,
    riskTakePoints: rule.takePoints,
    riskDeadline: rule.deadline,
    riskDeadlineDate: rule.deadline ? new Date(Date.parse(rule.deadline) + 8*3600000).toISOString().slice(0,10) : '',
    riskDeadlineHour: rule.deadline ? new Date(Date.parse(rule.deadline) + 8*3600000).toISOString().slice(11,13) : '',
    riskDeadlineMinute: rule.deadline ? new Date(Date.parse(rule.deadline) + 8*3600000).toISOString().slice(14,16) : '' 
  };

  Object.entries(values).forEach(([id, value]) => {
    const el = document.getElementById(id);
    if (el) el.value = value ?? '';
  });

  closeRiskDetail();
  document.getElementById('riskAlertSection')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  showRiskAlert(`已載入 ${rule.symbol}，修改後按「新增停損 / 停利設定」即可更新。`);
}

async function deleteRiskRule(uid) {
  if (!confirm('確定刪除這筆停損 / 獲利設定嗎？')) return;
  try {
    const result = await riskApi(`risk-rules/${encodeURIComponent(uid)}`, 'DELETE');
    guguRiskRules = result.rules.map(normalizeRiskRule);
    await loadRiskNotifications(); renderRiskRules(); renderRiskInbox();
    if (editingRiskRuleUid === uid) clearRiskForm();
    selectedRiskRuleUid = null;
  } catch (error) { showRiskAlert(error.message, 'warn'); }
}

function deleteSelectedRiskRule() {
  if (!selectedRiskRuleUid) return;
  const uid = selectedRiskRuleUid;
  closeRiskDetail();
  deleteRiskRule(uid);
}

function renderRiskInbox() {
  const badge = document.getElementById('riskMailBadge');
  const list = document.getElementById('riskMailList');
  const unreadCount = guguRiskNotifications.filter(item => item.unread).length;

  if (badge) {
    badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
    badge.style.display = unreadCount ? 'block' : 'none';
  }

  if (!list) return;
  if (!guguRiskNotifications.length) {
    list.innerHTML = '<div class="risk-mail-empty"><i class="fas fa-envelope-open"></i><br><br>目前沒有停損或獲利提醒</div>';
    return;
  }

  list.innerHTML = guguRiskNotifications.map(item => `
    <div class="risk-mail-item ${item.unread ? 'unread' : ''}" onclick="openRiskNotification('${escapeRiskHtml(item.id)}')">
      <div class="risk-mail-item-title">${escapeRiskHtml(item.title)} · ${escapeRiskHtml(item.symbol)} ${escapeRiskHtml(item.name)}</div>
      <div class="risk-mail-item-message">${escapeRiskHtml(item.message)}</div>
      <div class="risk-mail-item-time">${escapeRiskHtml(formatDeadline(item.createdAt))} · ${escapeRiskHtml(({pending:'Email 等待寄送',sending:'Email 寄送中',sent:'Email 已交給寄信服務',failed:'Email 寄送失敗',skipped:'站內通知',cancelled:'Email 已撤銷'})[item.emailState] || '站內通知')}</div>
    </div>
  `).join('');
}

function toggleRiskInbox(event) {
  if (event) event.stopPropagation();
  const panel = document.getElementById('riskMailPanel');
  if (!panel) return;
  panel.classList.toggle('open');
}

async function openRiskNotification(notificationId) {
  const notification = guguRiskNotifications.find(item => item.id === notificationId);
  if (!notification) return;
  try {
    await riskApi('risk-notifications','POST',{action:'read',id:notificationId});
    notification.unread = false; renderRiskInbox();
    document.getElementById('riskMailPanel')?.classList.remove('open');
    openRiskDetail(notification.ruleUid);
  } catch (error) { showRiskAlert(error.message,'warn'); }
}
async function markAllRiskNotificationsRead(event) {
  event?.stopPropagation();
  try { await riskApi('risk-notifications','POST',{action:'read'});await loadRiskNotifications();renderRiskInbox(); }
  catch (error) { showRiskAlert(error.message,'warn'); }
}
async function clearRiskNotifications(event) {
  event?.stopPropagation();
  if (!guguRiskNotifications.length || !confirm('確定清空站內提醒紀錄嗎？現有提醒設定仍會繼續監控。')) return;
  try { await riskApi('risk-notifications','POST',{action:'clear'});await loadRiskNotifications();renderRiskInbox(); }
  catch (error) { showRiskAlert(error.message,'warn'); }
}

document.addEventListener('click', event => {
  const mailPanel = document.getElementById('riskMailPanel');
  const mailWrap = document.querySelector('.risk-mail-wrap');
  if (mailPanel?.classList.contains('open') && mailWrap && !mailWrap.contains(event.target)) {
    mailPanel.classList.remove('open');
  }
});

document.addEventListener('keydown', event => {
  if (event.key === 'Escape') {
    document.getElementById('riskMailPanel')?.classList.remove('open');
    document.getElementById('riskDetailOverlay')?.classList.remove('open');
  }
});

document.addEventListener('DOMContentLoaded', async () => {
  try {
    await Promise.all([loadNotificationEmail(), loadRiskRules(), loadRiskNotifications()]);
    renderRiskRules();renderRiskInbox();await refreshRiskServer();
  } catch (error) { document.getElementById('notificationEmailStatus').textContent = error.message; }
  document.getElementById('riskSymbol')?.addEventListener('blur', autofillRiskQuote);
  ['riskBuyPrice','riskStopPercent','riskTakePercent'].forEach(id => document.getElementById(id)?.addEventListener('input', () => updateSuggestedRiskPrices(true)));
  document.getElementById('verifyEmailButton').addEventListener('click', async event => {
    const emailInput=document.getElementById('notificationEmail');
    if (!emailInput.reportValidity() || !emailInput.value.trim()) return;
    event.target.disabled=true;
    try {
      const result=await riskApi('email-settings','POST',{email:emailInput.value});
      await loadNotificationEmail();document.getElementById('notificationEmailStatus').textContent=result.message;
    } catch(error) { document.getElementById('notificationEmailStatus').textContent=error.message; }
    finally { event.target.disabled=false; }
  });
  const importButton=document.getElementById('importOldRiskRules');
  try { importButton.hidden=!JSON.parse(localStorage.getItem(GUGU_RISK_STORAGE_KEY) || '[]').length; } catch(error) {}
  importButton.addEventListener('click',async()=>{
    if (!confirm('舊提醒儲存在此瀏覽器，可能屬於其他帳號。確認這些提醒是你的，才匯入目前會員帳號；不會自動啟用 Email。')) return;
    importButton.disabled=true;
    try {
      const oldRules=JSON.parse(localStorage.getItem(GUGU_RISK_STORAGE_KEY) || '[]');
      for (const oldRule of oldRules) {
        if (guguRiskRules.some(r=>r.uid===String(oldRule.uid))) continue;
        const symbol=String(oldRule.symbol).replace(/\.TW[O]?$/,'');
        await riskApi('risk-rules','POST',{...oldRule,uid:String(oldRule.uid),symbol,market:['2729','7708','1268','5701'].includes(symbol)?'otc':'tse',emailEnabled:false,deadline:oldRule.deadline?new Date(oldRule.deadline).toISOString():''});
      }
      await refreshRiskServer();showRiskAlert('舊提醒已匯入目前帳號；原瀏覽器資料保留。');
    } catch(error){showRiskAlert(error.message,'warn');}finally{importButton.disabled=false;}
  });
  setInterval(async()=>{await refreshRiskServer();try{await loadNotificationEmail();}catch(error){}},30000);
});
</script>

<script>
var klineChart = null;
var klineSeries = null;

function initChart() {
    const container = document.getElementById('tv_chart_container');
    if (!container) {
        console.error("找不到圖表容器");
        return;
    }

    if (klineChart && klineSeries) return;

    klineChart = LightweightCharts.createChart(container, {
        width: container.clientWidth,
        height: 360,
        layout: {
            background: { color: '#111111' },
            textColor: '#d1d4dc',
        },
        grid: {
            vertLines: { color: 'rgba(42, 46, 57, 0.5)' },
            horzLines: { color: 'rgba(42, 46, 57, 0.5)' },
        },
        timeScale: {
            borderColor: 'rgba(197, 203, 206, 0.8)',
            timeVisible: true,
            secondsVisible: false,
        },
    });

    klineSeries = klineChart.addSeries(LightweightCharts.CandlestickSeries, {
        upColor: '#f23645',
        downColor: '#089981',
        borderVisible: false,
        wickUpColor: '#f23645',
        wickDownColor: '#089981',
    });

    window.addEventListener('resize', () => {
        klineChart.applyOptions({ width: container.clientWidth });
    });
}

function toggleRiskSection(button) {
    const panel = document.getElementById('riskAlertPanel');
    if (!panel || !button) return;
    const willOpen = panel.hidden;
    panel.hidden = !willOpen;
    button.setAttribute('aria-expanded', String(willOpen));
    button.classList.toggle('is-open', willOpen);
    const label = button.querySelector('span');
    if (label) label.textContent = willOpen ? '收合設定' : '展開設定';
}

fetch(`${PROXY_URL}/api/etf/snapshot`)
  .then(data => {
      console.log(data.quotes);
  });

async function loadKline() {
    if (!klineSeries) initChart();

    const symbolInput = document.getElementById('klineSymbol');
    const symbol = symbolInput ? symbolInput.value.trim() : '';
    if (!symbol) return alert("請輸入股票代號");

    try {
        const res = await fetch(`${PROXY_URL}/api/kline?symbol=${symbol}`);
        if (!res.ok) throw new Error("伺服器回應異常");

        const data = await res.json();

        if (data.error) {
            alert("找不到資料: " + data.error);
            return;
        }

        const formattedData = data.map(d => ({
            time: d.time,
            open: parseFloat(d.open),
            high: parseFloat(d.high),
            low: parseFloat(d.low),
            close: parseFloat(d.close)
        })).filter(d => !isNaN(d.open));

        if (!formattedData.length) {
            alert("沒有可用資料");
            return;
        }

        klineSeries.setData(formattedData);
        klineChart.timeScale().fitContent();
    } catch (err) {
        console.error("載入失敗:", err);
        alert("無法讀取 K 線資料（請確認 server.js 有啟動）");
    }
}

document.addEventListener('DOMContentLoaded', initChart);
</script>

</body>
</html>
