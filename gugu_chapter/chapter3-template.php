<?php
if (!isset($chapterIndex, $chapterNumber, $chapterTitle, $intro, $sections, $imageFile)) {
    http_response_code(500);
    exit('課程資料不完整');
}
$pageTitle = "第三章第{$chapterNumber}回｜{$chapterTitle}";
$chapterTitles = [
    1 => '常見的投資股票工具',
    2 => '常見分析方式',
    3 => '投資組合',
    4 => '降低風險',
    5 => '永續與責任投資',
    6 => '快打短線——短線與極短線交易策略',
    7 => '中線趨勢追蹤——中期與趨勢跟蹤策略',
    8 => '宏觀視野——長線與宏觀投資策略',
];
$previousChapterIndex = $chapterIndex > 1 ? $chapterIndex - 1 : null;
$nextChapterIndex = $chapterIndex < 8 ? $chapterIndex + 1 : null;
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="../styles/style.css" />
  <style>
    .chapter-topbar { max-width: 980px; margin: 24px auto 0; padding: 0 8px; }
    .chapter-logo { display: inline-block; color: #00f2ea; text-decoration: none; font-size: 1.8rem; font-weight: 700; text-shadow: 0 0 8px rgba(0, 242, 234, 0.45); }
    .chapter-logo:hover { color: #7ffff7; }
    .chapter-wrap { max-width: 980px; margin: 40px auto; padding: 24px; background: rgba(18, 22, 44, 0.92); border: 1px solid rgba(0, 255, 255, 0.25); border-radius: 16px; box-shadow: 0 0 24px rgba(0, 255, 255, 0.2); color: #e8f3ff; line-height: 1.8; }
    .chapter-wrap h1 { margin: 0 0 8px; color: #00f2ea; text-shadow: 0 0 10px rgba(0, 242, 234, 0.35); }
    .chapter-wrap h2 { margin-top: 24px; color: #f7c65e; font-size: 1.2rem; }
    .chapter-wrap .meta { color: #9ab3cc; margin-bottom: 16px; font-size: 0.95rem; }
    .chapter-nav { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
    .back-link, .next-link { display: inline-block; margin-top: 20px; color: #00f2ea; text-decoration: none; border: 1px solid rgba(0, 242, 234, 0.35); border-radius: 8px; padding: 8px 14px; }
    .back-link:hover, .next-link:hover { background: rgba(0, 242, 234, 0.08); }
    .chapter-illustration { margin-top: 22px; padding: 10px; border: 1px solid rgba(0, 242, 234, 0.25); border-radius: 14px; background: rgba(12, 16, 34, 0.75); }
    .chapter-illustration img { width: 100%; border-radius: 10px; display: block; }
  </style>
<?php require dirname(__DIR__) . '/includes/site-head.php'; ?>
<link rel="stylesheet" href="../styles/chapter-reading.css?v=<?= filemtime(dirname(__DIR__) . '/styles/chapter-reading.css') ?>">
</head>
<body class="site-layout">
<?php require dirname(__DIR__) . '/includes/site-header.php'; ?>
  
  <main class="chapter-wrap">
    <h1><?= htmlspecialchars("第三章第{$chapterNumber}回：{$chapterTitle}", ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="meta">GUGU 漫畫教室｜投資策略篇</div>
    <p class="chapter-intro"><?= htmlspecialchars(explode('。', $intro)[0] . '。', ENT_QUOTES, 'UTF-8') ?></p>
    <figure class="chapter-illustration">
      <img src="../assets/<?= htmlspecialchars($imageFile, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars("第三章第{$chapterNumber}回教學插圖", ENT_QUOTES, 'UTF-8') ?>" />
    </figure>
    <?php require dirname(__DIR__) . '/includes/chapter-notes.php'; ?>
    <nav class="chapter-nav" aria-label="課程導覽">
<?php if ($previousChapterIndex !== null): ?>
      <a class="back-link" href="chapter3-<?= $previousChapterIndex ?>.html">← 上一回：<?= htmlspecialchars($chapterTitles[$previousChapterIndex], ENT_QUOTES, 'UTF-8') ?></a>
<?php endif; ?>
      <a class="back-link" href="../main.php">回主頁</a>
<?php if ($nextChapterIndex !== null): ?>
      <a class="next-link" href="chapter3-<?= $nextChapterIndex ?>.html">下一回：<?= htmlspecialchars($chapterTitles[$nextChapterIndex], ENT_QUOTES, 'UTF-8') ?> →</a>
<?php endif; ?>
    </nav>
  </main>
</body>
</html>
