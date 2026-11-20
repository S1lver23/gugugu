<?php
$chapterIndex = 8; $chapterNumber = '八'; $chapterTitle = '股票的價格評估';
$intro = '股票價格是否合理，可利用估值指標與同業、歷史區間及公司成長性比較，但沒有任何單一指標能給出絕對答案。';
$sections = [
    ['title' => '本益比', 'content' => '本益比以股價相對每股盈餘衡量市場定價，解讀時要注意獲利是否穩定及產業差異。'],
    ['title' => '企業價值倍數', 'content' => '企業價值相關倍數會納入公司的資本結構，可用於比較營運規模相近的公司。'],
    ['title' => '股價淨值比', 'content' => '股價淨值比比較股價與每股淨值，仍需搭配資產品質、獲利能力與未來展望判斷。'],
];
$imageFile = '2-8.jpg'; require __DIR__ . '/chapter2-template.php';
