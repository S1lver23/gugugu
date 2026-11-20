<?php
$chapterIndex = 4; $chapterNumber = '四'; $chapterTitle = '什麼是 K 線圖？';
$intro = 'K 線以圖形呈現一段時間內的開盤、收盤、最高與最低價，常用於整理價格走勢。';
$sections = [
    ['title' => '一根 K 線的四個價格', 'content' => '實體與影線共同呈現開盤、收盤、最高及最低價，顏色則用來區分上漲或下跌。'],
    ['title' => '時間週期', 'content' => '日 K、週 K、月 K 代表不同觀察區間，同一檔股票在不同週期可能呈現不同趨勢。'],
    ['title' => '搭配其他資訊', 'content' => 'K 線記錄歷史價格，不保證未來方向；應搭配成交量、基本面與風險管理使用。'],
];
$imageFile = '2-4.jpg'; require __DIR__ . '/chapter2-template.php';
