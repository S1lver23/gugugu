<?php
$chapterIndex = 1; $chapterNumber = '一'; $chapterTitle = '股票的分類';
$intro = '股票可以依市值、股東權利、交易市場、成長特性與產業等不同角度分類，分類方式能幫助投資人理解標的特性。';
$sections = [
    ['title' => '依市值分類', 'content' => '市值通常以股價乘上流通在外股數計算。大型股與小型股的規模、波動及成長特性可能不同。'],
    ['title' => '依權利與市場分類', 'content' => '普通股與特別股的股東權利不同；上市、上櫃等交易市場也各有規範。'],
    ['title' => '依產業觀察', 'content' => '科技、金融、旅遊等產業會受到不同趨勢影響，分類有助於比較同產業公司與分散配置。'],
];
$imageFile = '2-1.jpg'; require __DIR__ . '/chapter2-template.php';
