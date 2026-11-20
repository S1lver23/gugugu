<?php
// 1. 組合 API 網址 (你剛剛成功抓到的那一串)
$url = "https://etfapi.yuantaetfs.com/ectranslation/api/trans?APIType=ETFBackstage&CompanyName=YUANTAFUNDS&PageName=%2F&DeviceId=8a76979a-0b5a-4c64-92aa-855194658f38&FuncId=ETFNAV%2FGetINAV_Data&AppName=ETF&Device=4&Platform=ETF";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json, text/plain, */*',
    'Accept-Language: zh-TW,zh;q=0.9,en-US;q=0.8,en;q=0.7,zh-CN;q=0.6',
    'Origin: https://www.yuantaetfs.com',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
curl_close($ch);

if ($response) {
    $result = json_decode($response, true);
    
    // 根據你貼的結構，資料藏在 $result['Data'] 裡面
    if (isset($result['Data'])) {
        $etf_list = $result['Data'];
        
        // ==========================================
        // 寫法 A：如果你只要抓某一檔特定的 ETF (例如：0056)
        // ==========================================
        $target_id = "0056";
        foreach ($etf_list as $etf) {
            if ($etf['ETF_ID'] === $target_id) {
                $price = (float)$etf['NOW_PRICE']; // 最新市價
                $nav   = (float)$etf['NOW_NAV'];   // 最新淨值
                
                // 計算折溢價公式
                $discount_premium = (($price - $nav) / $nav) * 100;
                // 四捨五入到小數點後兩位
                $discount_premium = round($discount_premium, 2);
                
                echo "<h2>【單檔查詢結果】</h2>";
                echo "ETF 代號: " . $etf['ETF_ID'] . "<br>";
                echo "最新市價: " . $price . "<br>";
                echo "預估淨值: " . $nav . "<br>";
                
                if ($discount_premium > 0) {
                    echo "折溢價率: <span style='color:red;'>+{$discount_premium}% (溢價)</span><br>";
                } elseif ($discount_premium < 0) {
                    echo "折溢價率: <span style='color:green;'>{$discount_premium}% (折價)</span><br>";
                } else {
                    echo "折溢價率: 0.00% (平價)<br>";
                }
                break;
            }
        }
        
        echo "<hr>";
        
        // ==========================================
        // 寫法 B：如果你想用表格一口氣印出全部的 ETF 折溢價
        // ==========================================
        echo "<h2>【全系列 ETF 即時折溢價總覽】</h2>";
        echo "<table border='1' cellpadding='8' style='border-collapse:collapse; text-align:center;'>";
        echo "<tr style='background-color:#f2f2f2;'>
                <th>ETF 代號</th>
                <th>最新市價</th>
                <th>預估淨值</th>
                <th>計算折溢價率</th>
              </tr>";
              
        foreach ($etf_list as $etf) {
            $price = (float)$etf['NOW_PRICE'];
            $nav   = (float)$etf['NOW_NAV'];
            
            // 防呆：如果淨值為 0 則不計算以免報錯
            if ($nav > 0) {
                $dp = round((($price - $nav) / $nav) * 100, 2);
                
                // 根據正負號決定顏色 (紅溢價、綠折價)
                if ($dp > 0) {
                    $dp_text = "<span style='color:red; font-weight:bold;'>+{$dp}%</span>";
                } elseif ($dp < 0) {
                    $dp_text = "<span style='color:green; font-weight:bold;'>{$dp}%</span>";
                } else {
                    $dp_text = "<span>0.00%</span>";
                }
            } else {
                $dp_text = "資料不全";
            }
            
            echo "<tr>";
            echo "<td>" . $etf['ETF_ID'] . "</td>";
            echo "<td>" . $price . "</td>";
            echo "<td>" . $nav . "</td>";
            echo "<td>" . $dp_text . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "未能在回傳資料中找到 Data 欄位。";
    }
} else {
    echo "請求 API 失敗";
}
?>