<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$result = [
    'ok' => false, 'message' => '', 'symbol' => '', 'name' => '',
    'price' => 0, 'previousClose' => 0, 'change' => 0, 'changePct' => 0,
    'open' => 0, 'high' => 0, 'low' => 0, 'volume' => 0, 'quoteTime' => 0,
    'source' => '臺灣證券交易所 MIS StockAPI'
];
try {
    $category = $_GET['category'] ?? '';
    $symbol = $_GET['symbol'] ?? '';
    if (!is_string($category) || !in_array($category, ['food', 'travel', 'aviation'], true) || !is_string($symbol)) {
        throw new RuntimeException('股票類別或代號無效');
    }
    $symbol = preg_replace('/\.TW$/', '', strtoupper(trim($symbol))) . '.TW';
    $items = json_decode(file_get_contents(__DIR__ . '/data/' . $category . '-data.json'), true, 512, JSON_THROW_ON_ERROR);
    $stock = null;
    foreach ($items as $item) {
        if ($item['symbol'] === $symbol) { $stock = $item; break; }
    }
    if (!$stock) throw new RuntimeException('找不到指定股票');
    $result['symbol'] = $symbol;
    $result['name'] = $stock['name'];
    $id = substr($symbol, 0, -3);
    // 清單中只有這四檔為上櫃；其餘為上市。不能以價格猜測市場。
    $market = in_array($id, ['2729', '7708', '1268', '5701'], true) ? 'otc' : 'tse';
    $url = 'https://mis.twse.com.tw/stock/api/getStockInfo.jsp?' . http_build_query([
        'ex_ch' => $market . '_' . $id . '.tw', 'json' => 1, 'delay' => 0
    ]);
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 12,
        CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: Mozilla/5.0', 'Referer: https://mis.twse.com.tw/stock/fibest.jsp']
    ]);
    $body = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    if ($body === false || $status !== 200) {
        error_log('[股票資訊] StockAPI 連線失敗：' . $symbol . ' HTTP ' . $status . ' ' . $error);
        throw new RuntimeException('StockAPI 暫時無法使用，報價以 0 顯示，請稍後重試。');
    }
    $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    if (($payload['rtcode'] ?? '') !== '0000') throw new RuntimeException('StockAPI 回應失敗，報價以 0 顯示。');
    $quote = null;
    foreach ($payload['msgArray'] ?? [] as $row) {
        if (($row['c'] ?? '') === $id && ($row['ex'] ?? '') === $market) { $quote = $row; break; }
    }
    if (!$quote || !is_numeric($quote['z'] ?? null) || (float)$quote['z'] <= 0) {
        throw new RuntimeException('StockAPI 未提供有效成交價，報價以 0 顯示。');
    }
    $number = static function ($value): float {
        return is_numeric($value) && is_finite((float)$value) && (float)$value >= 0 ? (float)$value : 0;
    };
    $result['price'] = $number($quote['z']);
    $result['previousClose'] = $number($quote['y'] ?? null);
    $result['change'] = $result['previousClose'] > 0 ? round($result['price'] - $result['previousClose'], 4) : 0;
    $result['changePct'] = $result['previousClose'] > 0 ? round($result['change'] / $result['previousClose'] * 100, 4) : 0;
    foreach (['open' => 'o', 'high' => 'h', 'low' => 'l', 'volume' => 'v'] as $field => $key) {
        $result[$field] = $number($quote[$key] ?? null);
        if (!is_numeric($quote[$key] ?? null)) error_log('[股票資訊] ' . $symbol . ' 缺少欄位：' . $key);
    }
    $date = (string)($quote['d'] ?? '');
    $time = (string)($quote['t'] ?? '');
    if (preg_match('/^\d{8}$/', $date) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
        $result['quoteTime'] = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2) . ' ' . $time;
    }
    $result['ok'] = true;
    $result['message'] = '已取得來源報價；非交易時段可能顯示最近交易日資料。';
} catch (Throwable $error) {
    error_log('[股票資訊] 讀取失敗：' . $error->getMessage());
    $result['message'] = $error instanceof RuntimeException ? $error->getMessage() : 'StockAPI 資料無法解析，報價以 0 顯示。';
}
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
