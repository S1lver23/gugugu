<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>古估股 - 登入</title>
  <?php require __DIR__ . '/includes/site-icons.php'; ?>
  <link rel="stylesheet" href="styles/style.css" />
  <link rel="stylesheet" href="styles/login.css?v=<?= filemtime(__DIR__.'/styles/login.css') ?>" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
</head>
<body class="login-page">
  <!-- 已移除頁面載入時依 localStorage 自動跳轉 main.php 的程式碼 -->

  <div id="login-overlay">
    <div class="login-ambient" aria-hidden="true">
      <svg class="login-neon login-neon--plane" viewBox="0 0 24 24"><path d="m22 2-7 20-4-9-9-4Z M11 13 22 2"/></svg>
      <svg class="login-neon login-neon--food" viewBox="0 0 24 24"><path d="M4 3v5a3 3 0 0 0 6 0V3 M7 3v18 M18 3c-3 3-4 7-1 9h3 M20 3v18"/></svg>
      <svg class="login-neon login-neon--compass" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m16 8-2 6-6 2 2-6Z"/></svg>
      <svg class="login-neon login-neon--coffee" viewBox="0 0 24 24"><path d="M4 8h12v6a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5Z M16 9h2a3 3 0 0 1 0 6h-2 M3 22h15 M7 2v2 M12 1v3"/></svg>
      <svg class="login-neon login-neon--luggage" viewBox="0 0 24 24"><rect x="4" y="7" width="16" height="14" rx="3"/><path d="M9 7V3h6v4 M8 11v6 M16 11v6 M8 21v1 M16 21v1"/></svg>
      <svg class="login-neon login-neon--flight" viewBox="0 0 24 24"><path d="m3 14 7-3V4a2 2 0 0 1 4 0v7l7 3v3l-7-2v4l3 2v1l-5-1-5 1v-1l3-2v-4l-7 2Z"/></svg>
    </div>

    <div class="login-main-title">古估股 GuGuStock</div>

    <div class="login-scene">
      <div class="login-mascots" aria-hidden="true">
        <span class="login-mascot login-mascot--chick"></span>
        <span class="login-mascot login-mascot--bull"></span>
        <span class="login-mascot login-mascot--bear"></span>
        <span class="login-mascot login-mascot--rabbit"></span>
        <span class="login-mascot login-mascot--octopus"></span>
      </div>
    <div class="login-card">

      <div class="login-title">LOGIN</div>

      <div class="login-input-group">
        <input type="text" id="usernameInput" name="account-id" placeholder="Username" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" aria-label="帳號">
      </div>

      <div class="login-input-group">
        <input type="password" id="passwordInput" name="password" placeholder="Password" autocomplete="current-password" aria-label="密碼">
        <i class="fas fa-eye toggle-password" id="togglePasswordBtn"></i>
      </div>

      <div class="login-input-group" id="registerEmailGroup" style="display:none"><input type="email" id="registerEmail" placeholder="信箱（用於提醒與找回密碼）" autocomplete="email"></div>
      <p id="registerPolicy" style="display:none;font-size:13px;line-height:1.6">密碼：至少 8 個字元</p>
      <div id="loginMsg" class="error-msg"></div>
      <button id="submitLoginBtn" class="login-submit-btn">登 入</button>

      <div class="links">
        <a href="#" id="registerLink">Don't have an account? <b>點我</b></a>
        <a href="#" id="forgotLink">忘記密碼?</a>
        <a href="#" id="backToLoginLink" style="display:none;">← 返回登入</a>
      </div>
    </div>
    </div>
  </div>

  <script src="scripts/account-policy.js?v=<?= filemtime(__DIR__ . '/scripts/account-policy.js') ?>"></script>
  <script src="scripts/script.js?v=<?= filemtime(__DIR__.'/scripts/script.js') ?>"></script>
</body>
</html>
