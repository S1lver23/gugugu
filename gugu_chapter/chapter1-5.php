<?php
$chapterIndex = 5;
$chapterNumber = '五';
$chapterTitle = '購買股票的交易成本';
$intro = '買賣股票除了成交價，還可能產生券商手續費與交易稅等成本，實際金額依市場規定與券商方案計算。';
$sections = [
    ['title' => '手續費', 'content' => '券商會依成交金額收取手續費，各家折扣與最低收費可能不同，開戶前可以先比較。'],
    ['title' => '交易稅與其他成本', 'content' => '賣出證券時可能需要負擔交易稅；頻繁交易還會讓手續費累積，進一步影響實際報酬。'],
    ['title' => '計算損益要完整', 'content' => '評估一筆投資時，應把買進與賣出的所有成本都納入，不能只比較成交價格的差額。'],
];
$imageFile = '1-5.jpg';
require __DIR__ . '/chapter-template.php';
