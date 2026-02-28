<?php
/**
 * 修正版 PHP 反向代理
 * 用于 Minecraft 服务器状态查询
 */

// 配置：后端服务器地址 - 直接指向你的 API 接口
$backend_url = 'http://localhost:8081/api/server/bedrock/localhost?port=443';

// 禁用错误输出，避免干扰 headers
error_reporting(0);
ini_set('display_errors', 0);

// 初始化cURL
$ch = curl_init();

// 设置目标URL - 直接使用完整的后端URL，不进行路径拼接
curl_setopt($ch, CURLOPT_URL, $backend_url);

// 设置请求方法
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

// 设置请求体（如果有）
$input = file_get_contents('php://input');
if ($input) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
}

// 设置请求头
$headers = getallheaders();
$curl_headers = [];
foreach ($headers as $name => $value) {
    // 过滤掉不需要转发的头部
    $name_lower = strtolower($name);
    if ($name_lower === 'host' || $name_lower === 'connection' || $name_lower === 'content-length') {
        continue;
    }
    $curl_headers[] = "$name: $value";
}
// 添加 X-Forwarded-* 头部
$curl_headers[] = 'X-Forwarded-For: ' . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR']);
$curl_headers[] = 'X-Forwarded-Proto: ' . (isset($_SERVER['HTTPS']) ? 'https' : 'http');
$curl_headers[] = 'X-Forwarded-Host: ' . $_SERVER['HTTP_HOST'];
curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_headers);

// 设置cURL选项
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);      // 获取返回内容
curl_setopt($ch, CURLOPT_HEADER, true);              // 需要返回头信息
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);     // 不自动跟随重定向
curl_setopt($ch, CURLOPT_TIMEOUT, 30);                // 超时时间

// 执行请求
$response = curl_exec($ch);
$error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

// PHP 8.0+ 不需要显式关闭 curl，它会自动清理
// 检查错误
if ($error) {
    // 清除之前可能的所有输出
    if (ob_get_level()) ob_clean();
    http_response_code(502);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Proxy Error: ' . $error,
        'data' => null
    ]);
    exit;
}

// 分割响应头和响应体
$response_headers = substr($response, 0, $header_size);
$response_body = substr($response, $header_size);

// 清除输出缓冲区，确保 headers 可以正常发送
if (ob_get_level()) ob_clean();

// 将后端返回的HTTP状态码发送给客户端
http_response_code($http_code);

// 发送响应头
$headers_lines = explode("\r\n", $response_headers);
foreach ($headers_lines as $line) {
    if (strpos($line, 'HTTP/') === 0) {
        // 跳过状态行
        continue;
    }
    if (!empty(trim($line))) {
        // 过滤某些头部
        if (stripos($line, 'Transfer-Encoding') === 0) {
            continue;
        }
        // 确保没有发送 Content-Length（如果有多个会冲突）
        if (stripos($line, 'Content-Length') === 0) {
            continue;
        }
        header($line);
    }
}

// 输出响应体
echo $response_body;
exit;