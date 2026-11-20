<?php
$chapterIndex = 2; $chapterNumber = '二'; $chapterTitle = '如何分析財報';
$intro = '分析財報可先從損益表、資產負債表與現金流量表著手，三張表應互相對照，而不是只看單一數字。';
$sections = [
    ['title' => '損益表', 'content' => '觀察公司在一段期間的收入、費用與獲利，並比較營收與獲利是否穩定成長。'],
    ['title' => '資產負債表', 'content' => '了解公司擁有的資產、承擔的負債與股東權益，評估財務結構是否健全。'],
    ['title' => '現金流量表', 'content' => '檢查營業、投資與籌資活動的現金流向，確認帳面獲利是否能帶來實際現金。'],
];
$imageFile = '2-2.jpg'; require __DIR__ . '/chapter2-template.php';
