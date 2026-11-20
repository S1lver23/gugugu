<?php
session_start();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>古估股 - 我的收藏</title>
    <link rel="stylesheet" href="../styles/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<?php require dirname(__DIR__) . '/includes/site-head.php'; ?>
</head>
<body class="site-layout cyber-theme">
<?php require dirname(__DIR__) . '/includes/site-header.php'; ?>

    

    <main class="content-area" style="padding: 20px; max-width: 1200px; margin: 0 auto;">
        <div class="section-header" style="margin-bottom: 30px;">
            <h2 style="color: #00f2ea;"><i class="fas fa-star" style="color: #ffcc00;"></i> 個人收藏清單</h2>
            <p style="color: #888;">追蹤您感興趣的餐飲、旅遊與航空個股</p>
        </div>

        <div class="stock-table-container">
            <table class="cyber-table">
                <thead>
                    <tr>
                        <th>代號</th>
                        <th>名稱</th>
                        <th>股價</th>
                        <th>漲跌</th>
                        <th>幅度</th>
                        <th>成交量</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="favorite-stock-list">
                    <tr><td colspan="7" style="text-align:center; padding:50px;">讀取中...</td></tr>
                </tbody>
            </table>
        </div>
    </main>

        <script src="../scripts/script.js?v=<?= filemtime(dirname(__DIR__) . '/scripts/script.js') ?>"></script>
    <script>
            document.addEventListener('DOMContentLoaded', async () => {
                await checkLoginStatus();
                initTicker();
                await syncFavorites();
                await renderFavorites();
                setInterval(renderFavorites, 10000);
            });

            async function renderFavorites() {
                const tbody = document.getElementById('favorite-stock-list');
                const allStocks = [...foodStocks, ...travelStocks, ...aviationStocks];
                const myFavorites = allStocks.filter(s => userFavorites.includes(s.id));

                if (myFavorites.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:50px; color:#888;">尚無收藏股票</td></tr>';
                    return;
                }

                await updateTableData(myFavorites, 'favorite-stock-list');
            }
    </script>
</body>
</html>


