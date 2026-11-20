<?php
// 僅供 PHP 內建伺服器的臨時 Tunnel 入口，勿用作正式部署。
$root = dirname(__DIR__);
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
if (str_contains($path, "\0") || preg_match('~(?:^|/)\.{1,2}(?:/|$)~', $path)) {
    http_response_code(404); exit;
}
// 相容舊版登入腳本及 XAMPP 書籤，統一轉到 Tunnel 根路徑。
if ($path === '/gugugu' || str_starts_with($path, '/gugugu/')) {
    $target = substr($path, strlen('/gugugu')) ?: '/';
    // 避免雙斜線被瀏覽器解讀為其他網站。
    $target = '/' . ltrim($target, '/');
    $query = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: ' . str_replace(["\r", "\n"], '', $target) . ($query !== '' ? '?' . $query : ''), true, 302);
    exit;
}
if ($path === '/') { header('Location: /login.php'); exit; }
if (str_starts_with($path, '/api/')) {
    // 先驗證外部同源，再轉成後端既有白名單來源；不移除 CSRF 檢查。
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($origin !== '' && $origin !== 'https://' . $host && $origin !== 'http://' . $host) {
        http_response_code(403); header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success'=>false,'message'=>'不允許的網站來源'], JSON_UNESCAPED_UNICODE); exit;
    }
    $headers = ['Origin: http://localhost'];
    foreach (['CONTENT_TYPE'=>'Content-Type', 'HTTP_COOKIE'=>'Cookie', 'HTTP_X_CSRF_TOKEN'=>'X-CSRF-Token'] as $key=>$name) {
        if (isset($_SERVER[$key])) $headers[] = $name . ': ' . $_SERVER[$key];
    }
    $ch = curl_init('http://127.0.0.1:4000' . $_SERVER['REQUEST_URI']);
    curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST=>$_SERVER['REQUEST_METHOD'], CURLOPT_HTTPHEADER=>$headers,
        CURLOPT_POSTFIELDS=>file_get_contents('php://input'), CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>30,
        CURLOPT_HEADERFUNCTION=>function ($ch, $line) {
            if (preg_match('/^(Content-Type|Set-Cookie|Cache-Control|Retry-After):/i', $line)) header(trim($line), false);
            return strlen($line);
        }]);
    $body = curl_exec($ch);
    http_response_code($body === false ? 502 : curl_getinfo($ch, CURLINFO_HTTP_CODE));
    if ($body === false) {
        error_log('Tunnel API 轉送失敗：' . curl_error($ch));
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success'=>false,'message'=>'後端服務暫時無法連線'], JSON_UNESCAPED_UNICODE);
    } else echo $body;
    curl_close($ch); exit;
}
$file = realpath($root . $path);
$relative = $file ? substr($file, strlen($root) + 1) : '';
$extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
$allowed = $file && str_starts_with($file, $root . '/') && is_file($file) && (
    (dirname($relative) === '.' && $extension === 'php' && basename($relative) !== 'stock_tool.php') ||
    (preg_match('~^(lists|gugu_chapter)/~', $relative) && $extension === 'php') ||
    (preg_match('~^gugu_chapter/chapter[1-5]-[1-9][0-9]*\.html$~', $relative) === 1) ||
    (preg_match('~^(assets|styles|scripts)/~', $relative) && in_array($extension, ['css','js','png','jpg','jpeg','gif','webp','svg','ico','mp4','woff','woff2','ttf'], true)) ||
    in_array($relative, ['manifest.json', 'data/tqaa_data.json', 'data/food-data.json', 'data/travel-data.json', 'data/aviation-data.json'], true)
);
if (!$allowed) { http_response_code(404); echo '找不到頁面'; exit; }
if ($extension === 'php') { chdir(dirname($file)); require $file; exit; }
return false;
