<?php
session_start();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>航空股列表 - 古估股</title>
  <link rel="stylesheet" href="../styles/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
  <style>
    /* 展覽區塊樣式 */
    .exhibition-section {
      max-width: 1200px; margin: 40px auto 20px auto; padding: 20px;
      background: rgba(30, 30, 35, 0.8); border: 1px solid #444; border-radius: 15px;
    }
    .exhibition-header {
      display: flex; align-items: center; gap: 12px; margin-bottom: 25px;
      color: #fff; font-size: 1.5rem; border-left: 6px solid #ff00ff; padding-left: 15px;
    }
    .exhibition-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr)); gap: 20px; align-items: stretch;
    }
    .exhibition-link { text-decoration: none; display: block; height: 100%; }
    .exhibition-card {
      background: rgba(255, 255, 255, 0.05); border: 1px solid #333; padding: 24px;
      border-radius: 12px; height: 100%; box-sizing: border-box; transition: all 0.3s ease;
      display: flex; flex-direction: column;
    }
    .exhibition-card:hover {
      transform: translateY(-5px); background: rgba(255, 255, 255, 0.1);
      border-color: #ff00ff; box-shadow: 0 5px 15px rgba(255, 0, 255, 0.2);
    }
    .exhibition-card h4 { color: #ffcc00; margin-bottom: 12px; font-size: 1.25rem; }
    .exhibition-card p { color: #ccc; font-size: 0.95rem; margin: 8px 0; display: flex; align-items: flex-start; gap: 10px; }
    .exhibition-card i { color: #ff00ff; margin-top: 4px; width: 18px; }
    .btn-visit { margin-top: auto; padding-top: 15px; font-size: 0.85rem; color: #ff00ff; font-weight: bold; }
    
    .bottom-action-container { width: 100%; text-align: center; padding: 40px 0 60px 0; }
  </style>
<?php require dirname(__DIR__) . '/includes/site-head.php'; ?>
</head>
<body class="site-layout">
<?php require dirname(__DIR__) . '/includes/site-header.php'; ?>

  

  <section class="topic-section">
    <div class="section-header">
      <h2>航空股列表</h2>
    </div>

    <div id="stock-list-container" class="stock-list-container"></div>

    <div class="exhibition-section">
      <div class="exhibition-header">
        <i class="fas fa-plane"></i> 近期航空與航太展覽資訊
      </div>
      
      <p class="exhibition-update-note">資料核對：2026/10/29｜展期、票務與入場資格請以主辦單位公告為準。</p>
      <div id="exhibition-list" class="exhibition-grid">
        </div>
    </div>

    <div class="bottom-action-container">
      <a href="../main.php" class="btn-more">返回主頁</a>
    </div>
  </section>

  <script>
    document.addEventListener('DOMContentLoaded', async () => {
      await checkLoginStatus();
      initTicker();

      // 1. 載入航空股
      const container = document.getElementById('stock-list-container');
      try {
        const response = await fetch('../data/aviation-data.json');
        if (!response.ok) throw new Error(`列表回應 ${response.status}`); 
        const aviationItems = await response.json();
        container.innerHTML = '';
        aviationItems.forEach(item => {
          const stockLink = document.createElement('a');
          stockLink.className = 'stock-list-item';
          stockLink.href = `../stock-detail.php?category=aviation&symbol=${item.symbol}`; 
          stockLink.innerHTML = `
            <img src="${item.image}" alt="${item.name}">
            <div class="stock-list-info">
              <h3>${item.name} (${item.symbol})</h3>
              <p>${item.description}</p>
            </div>
          `;
          container.appendChild(stockLink);
        });
      } catch (error) { console.error(error); container.textContent = '股票列表暫時無法載入，請重新整理頁面。'; }

      // 2. 載入航空展
      const exhibitionList = document.getElementById('exhibition-list');
      const exhibitions = [
        {
          "title": "2026 ITF 台灣國際旅展（航空公司參展）",
          "date": "2026-11-06 ～ 2026-11-09",
          "location": "台北南港展覽館一館",
          "url": "https://www.taiwanitf.org.tw/",
          "note": "旅展內含航空公司參展，非專門航空展。"
        },
        {
          "title": "2026 藍天使返航飛行展演",
          "date": "2026-11-06 ～ 2026-11-07",
          "location": "美國佛羅里達州彭薩科拉海軍航空站",
          "url": "https://www.naspensacolaairshow.org/information/faqs",
          "note": "飛行展示活動；入場規定請以主辦單位公告為準。"
        },
        {
          "title": "2026 MEBAA 中東商務航空展",
          "date": "2026-12-08 ～ 2026-12-10",
          "location": "阿拉伯聯合大公國杜拜 DWC 航展場地",
          "url": "https://www.mebaa.aero/",
          "note": "限商務航空業界人士，未滿 18 歲不得入場。"
        }
      ];

      exhibitions.forEach(ex => {
        const anchor = document.createElement('a');
        anchor.className = 'exhibition-link';
        anchor.href = ex.url;
        anchor.target = "_blank";
        anchor.rel = "noopener noreferrer";
        anchor.innerHTML = `
          <div class="exhibition-card">
            <h4>${ex.title}</h4>
            <p><i class="fas fa-calendar-alt"></i> ${ex.date}</p>
            <p><i class="fas fa-map-marker-alt"></i> ${ex.location}</p>
            ${ex.note ? `<p>${ex.note}</p>` : ''}
            <div class="btn-visit">前往官網 <i class="fas fa-external-link-alt"></i></div>
          </div>
        `;
        exhibitionList.appendChild(anchor);
      });
    });
  </script>
  <script src="../scripts/script.js?v=<?= filemtime(dirname(__DIR__) . '/scripts/script.js') ?>"></script>
</body>
</html>


