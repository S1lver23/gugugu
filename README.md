# GuGuGu 專題完整說明

本專題是一個以「美食 / 旅遊 / 航空」三大題材為核心的股票資訊平台，包含登入、主頁行情、收藏追蹤、個股詳情與教學章節。

## 1. 專題目標

1. 提供新手可理解的股票介面與主題式分類。
2. 用統一資料流顯示 ETF + 三大產業行情。
3. 提供登入後個人收藏功能，會員與收藏資料持久化到 MySQL。
4. 保持前端頁面風格一致（跑馬燈、搜尋、使用者區）。
5. 提供可維護、可快速回復上下文的文件與結構。

## 2. 執行方式

### 2.1 先決條件

1. 已安裝 Node.js（建議 LTS）。
2. 已啟用 XAMPP Apache，專案放在 htdocs。

### 2.2 安裝與啟動

```bash
npm install
npm start
```

1. `npm start` 會啟動後端 API 於 http://localhost:4000
2. 前端主入口：http://localhost/gugugu/login.php

### 2.3 若 npm 啟動失敗（常見）

若出現 iconv-lite / raw-body 亂碼錯誤，執行：

```bash
# Windows PowerShell
Remove-Item node_modules -Recurse -Force
Remove-Item package-lock.json -Force
npm install
npm start
```

## 3. 專案樹狀架構（目前實際）

```text
gugugu/
├─ login.php
├─ main.php
├─ stock-detail.php
├─ styles/
│  └─ style.css
├─ scripts/
│  ├─ script.js
│  ├─ search.js
│  ├─ gugu.js
│  └─ main-page.js
├─ lists/
│  ├─ food-list.php
│  ├─ travel-list.php
│  ├─ aviation-list.php
│  └─ favorite-list.php
├─ backend/
│  └─ server.js
├─ database/
│  └─ schema.sql
├─ data/
│  ├─ users.json（僅供一次性遷移，不供網站登入使用）
│  ├─ latest-market-cache.json
│  ├─ food-data.json
│  ├─ travel-data.json
│  ├─ aviation-data.json
│  └─ tqaa_data.json
├─ gugu_chapter/
│  ├─ chapter1-1.php
│  └─ chapter1-1.html
├─ assets/
│  ├─ GUGU.png
│  └─ 1-1.jpg
└─ package.json
```

## 4. 核心功能與資料流

### 4.1 登入系統

1. 帳密來源：MySQL `gugugu_db.users`
2. API：`POST /api/login`
3. 密碼僅以 bcrypt 雜湊儲存於 `password_hash`；後端沒有 JSON 回退登入路徑。
4. 成功後：
- Node.js Session 建立並重新產生 Session ID
- 前端只可儲存顯示名稱，不能作為登入依據
- 導向 `main.php`

### 4.1.1 安全設定

1. 後端 Session Cookie 啟用 `HttpOnly`、`SameSite=Lax`；正式環境必須設定 HTTPS 與 `SESSION_SECRET`。
2. CORS 僅接受 `ALLOWED_ORIGINS` 指定的網站來源；開發預設僅允許 localhost。
3. 收藏與登出操作需要登入後的 CSRF Token，登入、註冊與寫入操作設有請求限速。
4. `.htaccess` 會封鎖 `users.json`、快取檔、後端原始碼與稽核日誌的 HTTP 存取。Apache 必須啟用 `mod_rewrite`，並允許此專案使用 `.htaccess`。
5. 本機開發可複製 `.env.example` 為私有 `.env`；正式部署請以 `.env.production.example` 建立私有環境設定，填入高強度 `SESSION_SECRET`、非 root 的資料庫帳號及實際 HTTPS 網域。

### 4.2 主頁行情

1. 前端主資料來源：`GET /api/snapshot`
2. 顯示區塊：
- ETF 卡片（上方）
- 三大產業表格（美食 / 旅遊 / 航空）
- 市場情緒溫度計
3. 後端每 15 秒更新快照並寫入 `data/latest-market-cache.json`

### 4.3 收藏系統

1. 收藏資料儲存在 MySQL `gugugu_db.favorites`，並以會員數字 ID 關聯。
2. 讀取：`GET /api/favorites`
3. 更新：`POST /api/favorites`（`action=add/remove`）
4. 收藏頁路徑：`lists/favorite-list.php`

### 4.4 搜尋系統

1. 搜尋資料來源：
- `data/food-data.json`
- `data/travel-data.json`
- `data/aviation-data.json`
2. 命中後導向：`stock-detail.php?category=...&symbol=...`

### 4.5 教學章節

1. 第一章第一回：`gugu_chapter/chapter1-1.php`
2. 章節頁已支援：
- 左上角古估股返回主頁
- 文末教學圖片（`assets/1-1.jpg`）

## 5. 頁面一致性規範（已套用）

下列頁面均應具備：

1. 頂部跑馬燈（`ticker-wrap`）
2. 搜尋框（`header-search-input`）
3. 使用者顯示（`userInfo`）
4. 登出按鈕（`logoutBtn`）

涵蓋頁面：

1. `lists/food-list.php`
2. `lists/travel-list.php`
3. `lists/aviation-list.php`
4. `lists/favorite-list.php`

## 6. API 清單

### 6.1 資料 API

1. `GET /api/snapshot`
2. `GET /api/quote/:symbol`

### 6.2 身分 API

1. `POST /api/login`
2. `POST /api/logout`
3. `GET /api/status`
4. `GET /api/me`
5. `GET /api/csrf`（登入後取得寫入 API 的 CSRF Token）

### 6.3 收藏 API

1. `GET /api/favorites`
2. `POST /api/favorites`

## 7. 關鍵修正紀錄（本輪）

1. 將新密碼改為 bcrypt 雜湊，並在成功登入時遷移舊帳號格式。
2. 取消前端 localStorage 登入判斷，統一以 Node.js Session 驗證。
3. CORS 改為來源白名單，Session Cookie 加上安全屬性。
4. 登入、註冊與收藏寫入加入限速、CSRF 防護及 `logs/security.log` 稽核紀錄。
5. `.htaccess` 封鎖敏感 JSON、後端和日誌的 HTTP 存取。
6. 修復多處 .html 舊連結造成白頁
7. 修復 favorite-list 爆版（script 標籤插入錯位）
8. 將所有 list 頁統一移到 `lists/` 資料夾
9. 修復 list 頁搬移後相對路徑（styles/scripts/data/main）
10. 在 chapter1-1 頁面補上圖片與主頁連結

## 8. 維護與除錯指南

### 8.1 主頁顯示「讀取中」不更新

1. 檢查 `npm start` 是否啟動
2. 檢查 `http://localhost:4000/api/snapshot` 是否回 JSON
3. 瀏覽器 `Ctrl+F5` 強制重整
4. 確認前端是否在 `localhost/gugugu` 而非錯誤路徑

### 8.2 登入失敗

1. 確認 MySQL 已啟動，且 `gugugu_db.users` 存在帳號。
2. 確認 `.env` 或系統環境變數中的 `DB_HOST`、`DB_NAME`、`DB_USER`、`DB_PASSWORD` 正確。
3. 檢查後端 console 是否有錯誤。
4. 確認前端送到 `POST /api/login`。

### 8.3 收藏不持久

1. 確認已登入（session 存在）
2. 檢查 `GET /api/favorites` 是否回 200
3. 檢查 `POST /api/favorites` response
4. 檢查 `gugugu_db.favorites` 是否被更新。

## 9. Memory Recovery Notes（給未來維護者）

這段是「快速回憶」摘要：

1. 本專題前端在 Apache，後端在 Node 4000。
2. 主資料來源已統一走 `/api/snapshot`。
3. 帳密與收藏已改由 `gugugu_db` 的 `users` 與 `favorites` 資料表保存；後端不得回退讀取 JSON。
4. 舊 `users.json` 僅供 `npm run migrate-json-users` 一次性遷移使用。
5. list 頁都在 `lists/`，不要再放回根目錄。
6. 若看到亂碼/白頁，先查路徑，再查 CORS，再查 API。

## 共用跑馬燈與導覽列

導覽列 HTML 與選單項目集中在 `includes/site-header.php`；部署路徑與目前頁面判斷在 `includes/site-config.php`。外觀集中在 `styles/site-header.css`，新聞、手機選單及登入／登出集中在 `scripts/site-header.js`；股票搜尋共用 `scripts/search.js`。

新頁面在 `</head>` 前加入：

```php
<?php require __DIR__ . '/includes/site-head.php'; ?>
```

在 `<body class="site-layout">` 後加入：

```php
<?php require __DIR__ . '/includes/site-header.php'; ?>
```

位於 `lists/` 或 `gugu_chapter/` 的頁面改用 `dirname(__DIR__)`。共用元件會自行載入樣式、腳本、新聞與登入狀態，不需要複製導覽列，也不需要另行呼叫跑馬燈初始化。登入頁保留原有專用介面。

## Email 與伺服器風險提醒

首頁新增「通知信箱設定」，帳密登入維持原樣。使用者填入自己的收件信箱並按「寄送驗證信」，驗證連結限用一次、30 分鐘內有效。驗證後才可勾選每筆規則的 Email 提醒。更換信箱會取消驗證狀態與舊收件地址的待寄提醒。

會員信箱、驗證 Token 雜湊、提醒規則與站內通知儲存於 MySQL；使用 `database/reminders.sql`，啟動時自動建立所需資料表。後端每 30 秒監控一次，不依賴瀏覽器開啟。本機筆電必須保持開機，Node.js 與 MySQL 都須運行。

報價僅請求 TWSE MIS `getStockInfo.jsp`，依「上市／上櫃」市場設定查詢。價格觸發只使用同一臺北交易日、最近五分鐘內的有效成交價；不使用買賣掛價、昨收、手動目前價或舊快照觸發。缺少或過期價格透過 API 回傳 0，並記錄原因。期限通知依使用者設定時間觸發，不要求即時成交價。

每次儲存或修改規則後，同一觸發條件只建立一次通知。清空站內信箱只是隱藏紀錄，不刪除去重紀錄；要停止監控應刪除規則。寄信失敗最多重試五次並逐次延長間隔。SMTP 已接受但程序中斷的少數情況，重試仍可能重複投遞；相同事件使用固定 Message-ID。狀態「Email 已交給寄信服務」不保證收件匣投遞，請查看垃圾郵件。

舊瀏覽器提醒不會自動歸屬目前會員。可使用「匯入此瀏覽器的舊提醒」，確認資料屬於本人後才匯入；原資料保留且不自動啟用 Email。

私有 `.env` 填寫 SMTP 設定，應用程式密碼會移除空白，不會在前端、API 或日誌輸出。先執行：

```bash
npm run verify-smtp
```

此命令只驗證連線，不寄信。成功後才將私有 `.env` 的 `EMAIL_ENABLED` 設為 `true` 並重新啟動後端。正式部署需設定公開 HTTPS `APP_URL`；本機的 localhost 驗證連結須在執行網站的同一台電腦開啟。

```bash
npm test
DB_INTEGRATION=1 node --env-file-if-exists=.env --test tests/*.test.js
```

整合測試使用暫時會員並清除資料，寄信在測試中攔截，不會寄出任何測試郵件。

### 新聞跑馬燈

`GET /api/news` 只回傳記憶體快取，不會觸發外部請求。後端 `backend/news.js` 啟動時載入 `data/news-cache.json`，快取過期時更新；運行期間每 30 分鐘依序抓取 Google News RSS 的美食、旅遊、航空分類。每次請求限 10 秒、2 MB，更新鎖避免重疊。近 7 天新聞依日期整理、跨分類去重、每類最多 4 則交錯顯示。個別來源失敗保留該類舊資料；超過 7 天的新聞不再顯示。快取原子寫入且不納入 Git。API 提供各類更新時間與過期狀態，跑馬燈滑鼠停留可查看最後更新時間。此為新聞資料，與股票報價資料來源無關。

### 信箱驗證與密碼重設

登入仍使用帳號與密碼。新註冊需要填寫信箱，驗證信沿用通知信箱驗證機制；寄送失敗仍保留註冊帳號，登入後可重新寄送。既有使用者於通知信箱設定補綁及驗證即可。

忘記密碼輸入帳號後，僅寄到該帳號已驗證的通知信箱。API 對不存在／未驗證帳號使用相同回覆。`password_resets` 於啟動自動建立；一次性令牌僅儲存 SHA-256、有效 30 分鐘、同帳號一分鐘內不重寄。網址令牌放 fragment 並於頁面載入後移除，使用者明確提交新密碼才消耗。重設時檢查信箱仍已驗證且未變更，更新 bcrypt 密碼並撤銷該帳號現有登入。未驗證信箱者仍需專題團隊人工確認身分。
