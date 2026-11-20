<?php
// etf_crawler.php
// 放在 etf.php 同一個資料夾
// 輸出：120 檔 ETF，包含 id/name/price/nav/premium_discount/changePct

date_default_timezone_set('Asia/Taipei');

class TaiwanEtfCrawler
{
    private string $etfUrl = 'https://mis.twse.com.tw/stock/data/all_etf.txt';
    private string $stockInfoUrl = 'https://mis.twse.com.tw/stock/api/getStockInfo.jsp';

    public function fetchEtfs(int $limit = 120): array
    {
        $raw = $this->request($this->etfUrl . '?_=' . round(microtime(true) * 1000));

        $data = json_decode(trim($raw), true);
        if (!is_array($data)) {
            throw new RuntimeException('證交所回應格式異常。');
        }

        $rows = [];
        $this->collectEtfRows($data, $rows);

        if (count($rows) === 0) {
            throw new RuntimeException('有抓到 JSON，但完全找不到 ETF 明細資料。');
        }

        $result = [];

        foreach ($rows as $row) {
            if (!is_array($row)) continue;

            $id = trim((string)($row['a'] ?? $row['id'] ?? ''));
            if ($id === '') continue;

            $result[$id] = [
                'id' => $id,
                'name' => trim((string)($row['b'] ?? $row['name'] ?? $id)),
                // all_etf.txt 的成交價
                'price' => $this->toFloat($row['e'] ?? $row['price'] ?? null),
                // all_etf.txt 的預估淨值
                'nav' => $this->toFloat($row['f'] ?? $row['nav'] ?? null),
                // all_etf.txt 的折溢價幅度
                'premium_discount' => $this->toFloat($row['g'] ?? $row['premium_discount'] ?? null),
                // 先給 null，後面用 getStockInfo.jsp 補今日漲跌幅
                'changePct' => null,
            ];
        }

        $result = array_values($result);

        usort($result, function ($a, $b) {
            return strcmp($a['id'], $b['id']);
        });

        $result = array_slice($result, 0, $limit);

        // 補上今日漲跌幅。這就是你畫面中 -0.24% 那一行。
        $quoteMap = $this->fetchQuoteMap(array_column($result, 'id'));

        foreach ($result as &$etf) {
            $id = $etf['id'];
            if (!isset($quoteMap[$id])) continue;

            $quote = $quoteMap[$id];
            $lastPrice = $this->toFloat($quote['z'] ?? null); // 最新成交價
            $yesterdayClose = $this->toFloat($quote['y'] ?? null); // 昨收
            $quoteName = trim((string)($quote['n'] ?? ''));

            if ($quoteName !== '') {
                $etf['name'] = $quoteName;
            }

            if ($lastPrice !== null) {
                $etf['price'] = $lastPrice;
            }

            if ($lastPrice !== null && $yesterdayClose !== null && $yesterdayClose > 0) {
                $etf['changePct'] = round((($lastPrice - $yesterdayClose) / $yesterdayClose) * 100, 2);
            }
        }
        unset($etf);

        return $result;
    }

    private function collectEtfRows($data, array &$rows): void
    {
        // 有些欄位可能是 JSON 字串，要先解開
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (is_array($decoded)) {
                $this->collectEtfRows($decoded, $rows);
            }
            return;
        }

        if (!is_array($data)) return;

        // 單筆 ETF 資料：a=代號，b=名稱，e=市價，f=NAV，g=折溢價
        if ($this->isEtfRow($data)) {
            $rows[] = $data;
            return;
        }

        foreach ($data as $value) {
            $this->collectEtfRows($value, $rows);
        }
    }

    private function isEtfRow(array $row): bool
    {
        return isset($row['a']) && (isset($row['b']) || isset($row['e']) || isset($row['f']) || isset($row['g']));
    }

    private function fetchQuoteMap(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));
        $map = [];

        // 避免網址太長，分批查詢
        foreach (array_chunk($ids, 45) as $chunk) {
            $exCh = array_map(function ($id) {
                return 'tse_' . $id . '.tw';
            }, $chunk);

            $query = http_build_query([
                'ex_ch' => implode('|', $exCh),
                'json' => 1,
                'delay' => 0,
                '_' => round(microtime(true) * 1000),
            ]);

            try {
                $raw = $this->request($this->stockInfoUrl . '?' . $query, false);
                $data = json_decode(trim($raw), true);

                if (!is_array($data) || !isset($data['msgArray']) || !is_array($data['msgArray'])) {
                    continue;
                }

                foreach ($data['msgArray'] as $quote) {
                    if (!is_array($quote)) continue;
                    $id = trim((string)($quote['c'] ?? ''));
                    if ($id !== '') {
                        $map[$id] = $quote;
                    }
                }
            } catch (Throwable $e) {
                // 即時行情補資料失敗時，不讓整個 ETF 頁面掛掉；只是 changePct 顯示 --
                continue;
            }
        }

        return $map;
    }

    private function request(string $url, bool $mustSuccess = true): string
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json,text/plain,*/*',
                'Accept-Language: zh-TW,zh;q=0.9,en;q=0.8',
                'Referer: https://mis.twse.com.tw/stock/various-areas/etf-price/indicator-disclosure-etf?lang=zhHant',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/126 Safari/537.36',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
            ],
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('cURL 錯誤：' . $error);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($mustSuccess && ($httpCode < 200 || $httpCode >= 300)) {
            throw new RuntimeException("HTTP 狀態碼異常：{$httpCode}");
        }

        return $response;
    }

    private function toFloat($value): ?float
    {
        if ($value === null) return null;

        $value = trim((string)$value);
        if ($value === '' || $value === '-' || $value === '--' || $value === '未結出') return null;

        $value = str_replace([',', '%', '％'], '', $value);
        return is_numeric($value) ? round((float)$value, 4) : null;
    }
}

try {
    $crawler = new TaiwanEtfCrawler();
    $etfs = $crawler->fetchEtfs(120);

    $names = array_map(function ($etf) {
        return [
            'id' => $etf['id'],
            'name' => $etf['name'],
        ];
    }, $etfs);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'count' => count($etfs),
        'updatedAt' => date('Y/m/d H:i:s'),
        'names' => $names,
        'data' => $etfs,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);

    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
