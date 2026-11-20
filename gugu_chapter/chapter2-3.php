<?php
$chapterIndex = 3; $chapterNumber = '三'; $chapterTitle = '怎麼看盤？';
$intro = '看盤是觀察股價、成交量與漲跌幅等市場資訊，數字反映當下買賣雙方的交易結果。';
$sections = [
    ['title' => '股價與漲跌幅', 'content' => '股價是目前成交價格，漲跌幅通常比較當日價格與前一交易日收盤價的變化。'],
    ['title' => '成交量', 'content' => '成交量代表已成交的數量，可搭配價格變化觀察市場參與程度，但不能單獨判斷未來走勢。'],
    ['title' => '避免被短期情緒帶走', 'content' => '盤中數字變化快速，應搭配公司基本面與自己的投資計畫，不因單次波動衝動交易。'],
];
$imageFile = '2-3.jpg'; require __DIR__ . '/chapter2-template.php';
