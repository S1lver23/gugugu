<?php
require_once __DIR__ . '/site-config.php';
$siteMenu = [
    'food' => ['美食區', '/lists/food-list.php'], 'travel' => ['旅遊區', '/lists/travel-list.php'],
    'aviation' => ['航空區', '/lists/aviation-list.php'], 'etf' => ['ETF專區', '/etf.php'],
    'financial' => ['財報專區', '/financial.php'], 'favorites' => ['我的收藏', '/lists/favorite-list.php']
];
?>
<div class="ticker-wrap site-ticker" aria-label="近期新聞">
  <div class="ticker-heading">近期新聞</div>
  <div class="ticker-move ticker-status" id="ticker-content">正在讀取近期新聞…</div>
</div>
<header class="site-header" data-base="<?= htmlspecialchars($siteBase, ENT_QUOTES, 'UTF-8') ?>">
  <div class="header-container">
    <a class="site-brand" href="<?= htmlspecialchars($siteBase, ENT_QUOTES, 'UTF-8') ?>/main.php"><span class="logo">古估股</span></a>
    <nav class="main-nav" aria-label="主要導覽">
      <ul>
      <?php foreach ($siteMenu as $key => [$label, $path]): ?>
        <li><a href="<?= htmlspecialchars($siteBase . $path, ENT_QUOTES, 'UTF-8') ?>"<?= $siteActive === $key ? ' aria-current="page"' : '' ?>><?= $key === 'favorites' ? '<span aria-hidden="true">⭐</span> ' : '' ?><?= $label ?></a></li>
      <?php endforeach; ?>
      </ul>
    </nav>
    <div class="header-right">
      <div class="search-bar">
        <input type="search" placeholder="搜尋股票代碼或名稱…" aria-label="搜尋股票代碼或名稱" id="header-search-input">
        <button type="button" id="header-search-button" aria-label="搜尋"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="10" cy="10" r="6"/><path d="m15 15 6 6"/></svg></button>
      </div>
      <div class="user-profile">
        <span id="userInfo">使用者</span>
        <a class="site-login-link" href="<?= htmlspecialchars($siteBase, ENT_QUOTES, 'UTF-8') ?>/login.php">登入</a>
        <button type="button" id="logoutBtn" style="display:none;">登出</button>
      </div>
    </div>
    <button class="mobile-nav-toggle" type="button" aria-label="開啟導覽選單" aria-expanded="false">☰</button>
  </div>
</header>
