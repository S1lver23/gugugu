<?php
header('Content-Type: application/json; charset=utf-8');

$symbol = strtoupper(trim($_GET['symbol'] ?? ''));
if (!preg_match('/^[0-9A-Z]{4,6}$/', $symbol)) {
    echo json_encode([
        'ok' => false,
        'message' => '請輸入正確的股票代號。'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$baseUrl = 'https://openapi.twse.com.tw/v1';
$incomeEndpoints = [
    '一般業' => '/opendata/t187ap06_L_ci',
    '金融業' => '/opendata/t187ap06_L_basi',
    '金控業' => '/opendata/t187ap06_L_fh',
    '保險業' => '/opendata/t187ap06_L_ins',
    '證券期貨業' => '/opendata/t187ap06_L_bd',
    '異業' => '/opendata/t187ap06_L_mim'
];
$balanceEndpoints = [
    '一般業' => '/opendata/t187ap07_L_ci',
    '金融業' => '/opendata/t187ap07_L_basi',
    '金控業' => '/opendata/t187ap07_L_fh',
    '保險業' => '/opendata/t187ap07_L_ins',
    '證券期貨業' => '/opendata/t187ap07_L_bd',
    '異業' => '/opendata/t187ap07_L_mim'
];

function fetchJsonRows(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 18,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'User-Agent: Mozilla/5.0 GUGU-Financial-Section'
        ]
    ]);
    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $httpCode < 200 || $httpCode >= 300) {
        return [];
    }

    $data = json_decode($body, true);
    return is_array($data) ? $data : [];
}

function findBySymbol(string $baseUrl, array $endpoints, string $symbol): array {
    foreach ($endpoints as $industry => $path) {
        $rows = fetchJsonRows($baseUrl . $path);
        foreach ($rows as $row) {
            if (($row['公司代號'] ?? '') === $symbol) {
                return [$row, $industry, $path];
            }
        }
    }
    return [null, null, null];
}

function numberValue($value): ?float {
    if ($value === null) return null;
    $clean = str_replace([',', '%'], '', trim((string)$value));
    if ($clean === '' || $clean === '-' || $clean === '--') return null;
    return is_numeric($clean) ? (float)$clean : null;
}

function percent(?float $part, ?float $whole): ?float {
    if ($part === null || $whole === null || $whole == 0.0) return null;
    return round(($part / $whole) * 100, 2);
}

function pick(array $row, array $keys): ?float {
    foreach ($keys as $key) {
        if (array_key_exists($key, $row)) {
            $num = numberValue($row[$key]);
            if ($num !== null) return $num;
        }
    }
    return null;
}

[$income, $incomeIndustry, $incomePath] = findBySymbol($baseUrl, $incomeEndpoints, $symbol);
[$balance, $balanceIndustry, $balancePath] = findBySymbol($baseUrl, $balanceEndpoints, $symbol);

$monthlyRows = fetchJsonRows($baseUrl . '/opendata/t187ap05_L');
$monthly = null;
foreach ($monthlyRows as $row) {
    if (($row['公司代號'] ?? '') === $symbol) {
        $monthly = $row;
        break;
    }
}

if (!$income && !$balance && !$monthly) {
    echo json_encode([
        'ok' => false,
        'message' => '目前查不到這檔上市公司的財報資料，可能是代號錯誤或非上市公司。'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$revenue = $income ? pick($income, ['營業收入', '收益', '收入']) : null;
$grossProfit = $income ? pick($income, ['營業毛利（毛損）淨額', '營業毛利（毛損）']) : null;
$operatingIncome = $income ? pick($income, ['營業利益（損失）', '營業利益']) : null;
$netIncome = $income ? pick($income, ['本期淨利（淨損）', '淨利（淨損）歸屬於母公司業主']) : null;
$eps = $income ? numberValue($income['基本每股盈餘（元）'] ?? null) : null;

$assets = $balance ? pick($balance, ['資產總額']) : null;
$liabilities = $balance ? pick($balance, ['負債總額']) : null;
$equity = $balance ? pick($balance, ['權益總額', '歸屬於母公司業主之權益合計']) : null;
$bookValue = $balance ? numberValue($balance['每股參考淨值'] ?? null) : null;

echo json_encode([
    'ok' => true,
    'symbol' => $symbol,
    'companyName' => $income['公司名稱'] ?? $balance['公司名稱'] ?? $monthly['公司名稱'] ?? '',
    'industryType' => $incomeIndustry ?? $balanceIndustry ?? '',
    'period' => [
        'year' => $income['年度'] ?? $balance['年度'] ?? '',
        'quarter' => $income['季別'] ?? $balance['季別'] ?? '',
        'reportDate' => $income['出表日期'] ?? $balance['出表日期'] ?? ''
    ],
    'income' => [
        'revenue' => $revenue,
        'grossProfit' => $grossProfit,
        'operatingIncome' => $operatingIncome,
        'netIncome' => $netIncome,
        'eps' => $eps,
        'grossMargin' => percent($grossProfit, $revenue),
        'operatingMargin' => percent($operatingIncome, $revenue),
        'netMargin' => percent($netIncome, $revenue)
    ],
    'balance' => [
        'assets' => $assets,
        'liabilities' => $liabilities,
        'equity' => $equity,
        'bookValuePerShare' => $bookValue,
        'debtRatio' => percent($liabilities, $assets)
    ],
    'monthlyRevenue' => [
        'year' => $monthly['出表日期'] ?? '',
        'month' => $monthly['資料年月'] ?? $monthly['月份'] ?? '',
        'currentMonthRevenue' => numberValue($monthly['營業收入-當月營收'] ?? $monthly['當月營收'] ?? null),
        'lastMonthRevenue' => numberValue($monthly['營業收入-上月營收'] ?? $monthly['上月營收'] ?? null),
        'lastYearMonthRevenue' => numberValue($monthly['營業收入-去年當月營收'] ?? $monthly['去年當月營收'] ?? null),
        'mom' => numberValue($monthly['營業收入-上月比較增減(%)'] ?? $monthly['上月比較增減(%)'] ?? null),
        'yoy' => numberValue($monthly['營業收入-去年同月增減(%)'] ?? $monthly['去年同月增減(%)'] ?? null)
    ],
    'sources' => [
        'income' => $incomePath,
        'balance' => $balancePath,
        'monthlyRevenue' => '/opendata/t187ap05_L'
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
