<?php
session_start();
$guideCategory = $_GET['category'] ?? '';
$guideOptions = [
    'food' => ['美食導引', 'assets/food-guide.jpeg'],
    'travel' => ['旅遊導引', 'assets/travel-guide.jpeg'],
    'aviation' => ['航空導引', 'assets/aviation-guide.jpeg'],
];
[$guideTitle, $guideImage] = $guideOptions[is_string($guideCategory) ? $guideCategory : ''] ?? $guideOptions['food'];
$guideUrl = '';
$stockInfoUrl = '';
$requestedSymbol = $_GET['symbol'] ?? '';
$requestedSymbol = is_string($requestedSymbol) ? strtoupper(trim($requestedSymbol)) : '';
$requestedSymbol = preg_replace('/\.TW$/', '', $requestedSymbol) . '.TW';
if (is_string($guideCategory) && isset($guideOptions[$guideCategory])) {
    $detailItems = json_decode(file_get_contents(__DIR__ . '/data/' . $guideCategory . '-data.json'), true);
    foreach (is_array($detailItems) ? $detailItems : [] as $detailItem) {
        if (($detailItem['symbol'] ?? '') !== $requestedSymbol) continue;
        $yahooSymbol = in_array(substr($requestedSymbol, 0, -3), ['2729', '7708', '1268', '5701'], true) ? substr($requestedSymbol, 0, -3) . '.TWO' : $requestedSymbol;
        $stockInfoUrl = 'https://tw.stock.yahoo.com/quote/' . rawurlencode($yahooSymbol);
        $candidate = $detailItem['links']['guide'] ?? '';
        if (filter_var($candidate, FILTER_VALIDATE_URL) && parse_url($candidate, PHP_URL_SCHEME) === 'https') {
            $guideUrl = $candidate;
        }
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>個股詳情 - 古估股</title>
  <link rel="stylesheet" href="styles/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
<?php require __DIR__ . '/includes/site-head.php'; ?>
</head>
<body class="site-layout">
<?php require __DIR__ . '/includes/site-header.php'; ?>

  

  <section id="food-detail" class="topic-section">
    <div class="food-pop-header">
      <h2 id="detail-title">讀取中...</h2>
    </div>
    <div class="food-category-grid">
      <a id="link-guide" href="<?= htmlspecialchars($guideUrl, ENT_QUOTES, 'UTF-8') ?>" class="category-card">
        <img src="<?= $guideImage ?>" alt="<?= $guideTitle ?>" class="category-card-image">
        <div class="category-card-title"><?= $guideTitle ?></div>
      </a>
      <a id="link-news" href="#" target="_blank" class="category-card">
        <img src="assets/realtime-news.jpeg" alt="即時新聞" class="category-card-image">
        <div class="category-card-title">即時新聞</div>
      </a>
     <a id="link-stock" href="<?= htmlspecialchars($stockInfoUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="category-card">
        <img src="assets/stock-info.jpeg" alt="股票資訊" class="category-card-image">
        <div class="category-card-title">股票資訊</div>
      </a>
    </div>
    <div style="text-align: center; margin-top: 40px;">
      <a href="lists/food-list.php" class="btn-more">返回列表</a>
    </div>
  </section>

  <script>
    document.addEventListener('DOMContentLoaded', async () => {
        // =========================================================
        // 1. 使用者登入狀態與登出邏輯 (修正後：點擊直接跳轉 login.php)
        // =========================================================
        // =========================================================
        // 2. 個股資料載入邏輯
        // =========================================================
        const urlParams = new URLSearchParams(window.location.search);
        const symbol = urlParams.get('symbol');
        const category = urlParams.get('category');
        
        const titleEl = document.getElementById('detail-title');
        const backButton = document.querySelector('.topic-section .btn-more');

        if (!symbol || !category) {
            titleEl.textContent = '錯誤：未指定股票代號或類別';
            return;
        }

        const dataFile = `data/${category}-data.json`;
        const listPage = `lists/${category}-list.php`; 

        try {
            const response = await fetch(dataFile, { cache: 'no-store' });
            if (!response.ok) throw new Error('個股資料讀取失敗'); 
            const items = await response.json();
            const normalizedSymbol = symbol.trim().toUpperCase().replace(/\.TW$/, '') + '.TW';
            const stockData = items.find(item => item.symbol === normalizedSymbol);
            
            if (stockData) {
                document.title = `${stockData.name} - ${category}區`;
                titleEl.textContent = `關於 ${stockData.name} (${stockData.symbol})`;
                
                document.getElementById('link-guide').href = stockData.links.guide;
                document.getElementById('link-news').href = stockData.links.news;
                document.getElementById('link-stock').href = stockData.links.stock;

                if (backButton) {
                    backButton.href = listPage;
                }
            } else {
                titleEl.textContent = `錯誤：找不到股票 ${symbol} 的資料`;
            }
        } catch (error) {
            console.error('載入資料失敗:', error);
            titleEl.textContent = '資料載入失敗';
        }
    });
  </script>
  <script src="scripts/script.js?v=<?= filemtime(__DIR__ . '/scripts/script.js') ?>"></script>
</body>
</html>


