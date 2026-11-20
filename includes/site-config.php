<?php
// 所有共用元件皆由這裡取得部署路徑，兼容根目錄及子目錄頁面。
$siteBase = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/gugugu/main.php'), '/');
if (in_array(basename($siteBase), ['lists', 'gugu_chapter'], true)) $siteBase = rtrim(dirname($siteBase), '/');
if ($siteBase === '.' || $siteBase === '/') $siteBase = '';
$siteCurrentFile = basename($_SERVER['SCRIPT_NAME'] ?? '');
$siteActive = [
    'food-list.php' => 'food', 'travel-list.php' => 'travel', 'aviation-list.php' => 'aviation',
    'favorite-list.php' => 'favorites', 'etf.php' => 'etf', 'financial.php' => 'financial'
][$siteCurrentFile] ?? '';
if (in_array($siteCurrentFile, ['stock-detail.php', 'stock-info.php'], true)) {
    $siteCategory = $_GET['category'] ?? '';
    if (is_string($siteCategory) && in_array($siteCategory, ['food', 'travel', 'aviation'], true)) $siteActive = $siteCategory;
}
