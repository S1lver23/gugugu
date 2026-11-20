<details class="chapter-notes">
  <summary>查看文字解說與補充提醒</summary>
  <div class="chapter-notes-body">
    <p><?= htmlspecialchars($intro, ENT_QUOTES, 'UTF-8') ?></p>
<?php foreach ($sections as $index => $section): ?>
    <h2><?= $index + 1 ?>. <?= htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8') ?></h2>
    <p><?= htmlspecialchars($section['content'], ENT_QUOTES, 'UTF-8') ?></p>
<?php endforeach; ?>
  </div>
</details>
