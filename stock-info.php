<?php
$category = $_GET['category'] ?? '';
$symbol = $_GET['symbol'] ?? '';
$category = is_string($category) && in_array($category, ['food', 'travel', 'aviation'], true) ? $category : '';
$symbol = is_string($symbol) ? preg_replace('/\.TW$/', '', strtoupper(trim($symbol))) . '.TW' : '';
$stockName = '';
if ($category) {
    $items = json_decode(file_get_contents(__DIR__ . '/data/' . $category . '-data.json'), true);
    foreach (is_array($items) ? $items : [] as $item) {
        if ($item['symbol'] === $symbol) { $stockName = $item['name']; break; }
    }
}
$valid = $stockName !== '';
$title = $valid ? $stockName . '（' . $symbol . '）' : '找不到指定股票';
$back = $valid ? 'stock-detail.php?' . http_build_query(['category' => $category, 'symbol' => $symbol]) : 'main.php';
function escapeStockText($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= escapeStockText($title) ?> - 股票資訊</title>
<link rel="stylesheet" href="styles/style.css">
<style>
body{background:#0d1020;color:#eef0ff;margin:0;font-family:system-ui,sans-serif}
.quote-page{max-width:920px;margin:auto;padding:36px 22px 70px}.quote-top{display:flex;align-items:center;justify-content:space-between;gap:16px}.quote-top a{color:#64ffda;text-decoration:none}.quote-top button{background:#ed00ed;color:#fff;border:0;border-radius:10px;padding:12px 18px;cursor:pointer}.quote-top button:disabled{opacity:.5;cursor:wait}
h1{font-size:clamp(24px,4vw,36px);margin:30px 0 8px}.quote-subtitle{color:#a7aecb}.quote-summary{margin:24px 0;padding:28px;background:#191e34;border:1px solid #343d66;border-radius:18px}.quote-price{font-size:clamp(40px,8vw,60px);font-weight:700;margin:8px 0}.quote-change{font-size:20px}.quote-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.quote-item{background:#191e34;border:1px solid #343d66;border-radius:14px;padding:20px}.quote-item dt{color:#a7aecb;margin-bottom:10px}.quote-item dd{font-size:25px;font-weight:600;margin:0}.quote-source{font-size:14px;color:#a7aecb;line-height:1.8;margin-top:22px}.quote-status{line-height:1.7;color:#ffd166}.quote-up{color:#ff6b81}.quote-down{color:#64ffda}@media(max-width:600px){.quote-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.quote-summary{padding:22px}}
</style>
<?php require __DIR__ . '/includes/site-head.php'; ?>
</head>
<body class="site-layout">
<?php require __DIR__ . '/includes/site-header.php'; ?>
<main class="quote-page" data-category="<?= escapeStockText($category) ?>" data-symbol="<?= escapeStockText($symbol) ?>">
<div class="quote-top"><a href="<?= escapeStockText($back) ?>">← 返回個股頁</a><button id="refreshQuote" type="button" <?= $valid ? '' : 'disabled' ?>>重新整理報價</button></div>
<h1><?= escapeStockText($title) ?></h1><p class="quote-subtitle">股票資訊 · 臺灣證券交易所 MIS</p>
<section class="quote-summary" aria-label="成交報價"><div>成交價（元）</div><div id="quotePrice" class="quote-price">0.00</div><div id="quoteChange" class="quote-change">漲跌 0.00（0.00%）</div></section>
<p id="quoteStatus" class="quote-status" role="status"><?= $valid ? '正在讀取來源報價…' : '請從美食、旅遊或航空區選擇股票。' ?></p>
<dl class="quote-grid">
<div class="quote-item"><dt>昨收（元）</dt><dd id="previousClose">0.00</dd></div>
<div class="quote-item"><dt>開盤（元）</dt><dd id="openPrice">0.00</dd></div>
<div class="quote-item"><dt>最高（元）</dt><dd id="highPrice">0.00</dd></div>
<div class="quote-item"><dt>最低（元）</dt><dd id="lowPrice">0.00</dd></div>
<div class="quote-item"><dt>累積成交量</dt><dd id="volume">0</dd></div>
<div class="quote-item"><dt>報價日期</dt><dd id="quoteDate" style="font-size:19px">0</dd></div>
</dl>
<div class="quote-source">來源報價時間：<span id="quoteTime">0</span><br>資料來源：TWSE MIS StockAPI。缺少或無法取得的數值以 0 顯示；成交量按來源數值呈現。<br>報價時間採用來源欄位，請注意交易日期及資料延遲。</div>
</main>
<script src="scripts/stock-info.js"></script>
</body>
</html>
