<?php
// 存档下载脚本
$worldsDir = __DIR__ . '/../worlds/我们做朋友吧~';

// 设置错误处理
function sendError($message, $code = 500) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// 检查目录是否存在
if (!is_dir($worldsDir)) {
    sendError('存档目录不存在', 404);
}

// 检查目录是否可读
if (!is_readable($worldsDir)) {
    sendError('存档目录不可读', 403);
}

// 创建临时 ZIP 文件
$zipFile = tempnam(sys_get_temp_dir(), 'world_') . '.zip';

$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    sendError('无法创建 ZIP 文件');
}

try {
    // 获取目录的实时路径（处理符号链接等）
    $realWorldsDir = realpath($worldsDir);
    if ($realWorldsDir === false) {
        throw new Exception("无法获取目录真实路径");
    }
    
    // 确保路径以目录分隔符结尾，便于截取相对路径
    $baseDir = rtrim($realWorldsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $baseDirLength = strlen($baseDir);
    
    /**
     * 递归添加文件和目录到 ZIP
     * 
     * @param string $currentDir 当前处理的目录
     * @param string $baseDir 基础目录（用于计算相对路径）
     * @param ZipArchive $zip ZIP 对象
     * @param int &$fileCount 文件计数引用
     * @param int &$dirCount 目录计数引用
     */
    function addToZip($currentDir, $baseDir, $zip, &$fileCount, &$dirCount) {
        // 打开目录
        $handle = opendir($currentDir);
        if ($handle === false) {
            throw new Exception("无法打开目录: " . $currentDir);
        }
        
        while (($entry = readdir($handle)) !== false) {
            // 跳过 . 和 ..
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            
            $fullPath = $currentDir . DIRECTORY_SEPARATOR . $entry;
            $relativePath = substr($fullPath, strlen($baseDir));
            
            // 统一使用正斜杠作为 ZIP 内部路径分隔符（兼容性更好）
            $zipPath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
            
            if (is_dir($fullPath)) {
                // 是目录：先添加空目录，再递归处理
                if (!$zip->addEmptyDir($zipPath)) {
                    throw new Exception("无法添加目录: " . $zipPath);
                }
                $dirCount++;
                
                // 递归处理子目录
                addToZip($fullPath, $baseDir, $zip, $fileCount, $dirCount);
                
            } else {
                // 是文件：添加到 ZIP
                if (!$zip->addFile($fullPath, $zipPath)) {
                    throw new Exception("无法添加文件: " . $zipPath);
                }
                $fileCount++;
            }
        }
        
        closedir($handle);
    }
    
    $fileCount = 0;
    $dirCount = 0;
    
    // 开始递归处理
    addToZip($realWorldsDir, $baseDir, $zip, $fileCount, $dirCount);
    
    // 检查是否为空
    if ($fileCount === 0 && $dirCount === 0) {
        throw new Exception("存档目录为空");
    }
    
} catch (Exception $e) {
    $zip->close();
    @unlink($zipFile);
    sendError($e->getMessage());
}

$zip->close();

if (!file_exists($zipFile) || filesize($zipFile) === 0) {
    @unlink($zipFile);
    sendError('压缩失败或文件为空');
}

// 获取文件大小
$fileSize = filesize($zipFile);

// 生成文件名：使用更友好的格式
$date = date('Y-m-d');
$filename = "Bedrock_Level_{$date}.zip";

// 发送下载头
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Content-Transfer-Encoding: binary');
header('Accept-Ranges: bytes');

// 清空输出缓冲区
if (ob_get_level()) {
    ob_end_clean();
}

// 输出文件内容
$fp = fopen($zipFile, 'rb');
if ($fp === false) {
    @unlink($zipFile);
    sendError('无法读取临时文件');
}

while (!feof($fp)) {
    echo fread($fp, 8192);
    flush();
}
fclose($fp);

// 删除临时文件
@unlink($zipFile);
exit;
?>
