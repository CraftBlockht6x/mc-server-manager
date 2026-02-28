<?php
// 配置（可根据需要修改）
$serverAddress = 'xiaohei.chaxil.top';  // 服务器地址
$serverPort = 443;  // 服务器端口
$serverType = 'bedrock';          // 服务器版本，'bedrock'或 'java'
$serverName = '我们做朋友吧~';        // 添加服务器时显示的名称

$apiProxyUrl = 'proxy.php';       // 相对路径

// 存档目录路径（相对于此文件）
$worldsDir = __DIR__ . '/../worlds/我们做朋友吧~';
$hasWorld = is_dir($worldsDir);

// 获取服务器状态（用于判断是否可以下载）
function getServerStatus($apiUrl) {
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // PHP 8.0+ 不需要 curl_close()，句柄会自动释放
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if ($data && $data['success'] && $data['data']['online']) {
                return [
                    'online' => true,
                    'players' => $data['data']['players'] ?? ['online' => 0, 'max' => 0]
                ];
            }
        }
    } catch (Exception $e) {
        error_log('获取服务器状态失败: ' . $e->getMessage());
    }
    return ['online' => false, 'players' => ['online' => 0, 'max' => 0]];
}

// 检查是否有玩家在线
$serverStatus = getServerStatus($apiProxyUrl);
$playersOnline = $serverStatus['online'] ? ($serverStatus['players']['online'] ?? 0) : 0;
$canDownload = $hasWorld && $playersOnline === 0;
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minecraft 服务器状态</title>
    <style>
        body {
            background-color: #1a1a1a;
            margin: 0;
            padding: 20px;
            font-family: 'Minecraft_English', 'Minecraft_Chinese', monospace;
        }
        .actions {
            margin-top: 20px;
            text-align: center;
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            color: white;
            border: none;
            cursor: pointer;
            font-family: 'Minecraft_English', 'Minecraft_Chinese', monospace;
            font-size: 16px;
        }
        .btn.add-server {
            background-color: #2196F3;
        }
        .btn.add-server:hover {
            background-color: #0b7dda;
        }
        .btn.download {
            background-color: #FF9800;
        }
        .btn.download:hover:not(:disabled) {
            background-color: #e68900;
        }
        .btn.download:disabled {
            background-color: #666;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .btn.download.downloading {
            background-color: #4CAF50;
            cursor: wait;
        }
        .btn.download.blocked {
            background-color: #F44336;
        }
        .download-status {
            margin-top: 10px;
            padding: 10px;
            border-radius: 5px;
            background-color: rgba(0, 0, 0, 0.5);
            color: #FFAA00;
            font-size: 14px;
            display: none;
        }
        .download-status.show {
            display: block;
        }
        .download-status.online {
            color: #FF5555;
        }
        .toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 12px 24px;
            border-radius: 5px;
            font-family: 'Minecraft_English', 'Minecraft_Chinese', monospace;
            z-index: 1000;
            animation: slideUp 0.3s ease;
            border-left: 4px solid #00A3E8;
        }
        @keyframes slideUp {
            from {
                bottom: 0;
                opacity: 0;
            }
            to {
                bottom: 20px;
                opacity: 1;
            }
        }
        .progress-bar {
            width: 100%;
            height: 4px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            background-color: #00A3E8;
            width: 0%;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <div id="minecraft-server-checker"></div>
    
    <div class="actions">
        <a class="btn add-server" 
           href="minecraft:?addExternalServer=<?php echo rawurlencode($serverName . '|' . $serverAddress . ':' . $serverPort); ?>"
           onclick="showToast('正在打开 Minecraft...', 'info')">
           添加服务器到Minecraft
        </a>
        
        <?php if ($hasWorld): ?>
        <button class="btn download <?php echo $canDownload ? '' : 'blocked'; ?>" 
                id="downloadWorldBtn" 
                onclick="downloadWorld()"
                <?php echo $canDownload ? '' : 'disabled'; ?>>
            <?php echo $canDownload ? '下载存档' : '有玩家在线，不可下载'; ?>
        </button>
        <?php else: ?>
        <button class="btn download" disabled>存档暂不可用</button>
        <?php endif; ?>
    </div>
    
    <div class="download-status <?php echo ($hasWorld && !$canDownload && $playersOnline > 0) ? 'show online' : ''; ?>" id="downloadStatus">
        <?php if ($playersOnline > 0): ?>
        ⚠️ 当前有 <?php echo $playersOnline; ?> 名玩家在线，请等待玩家下线后再下载存档
        <?php endif; ?>
    </div>

    <script src="minecraft-server-checker-v1.js"></script>
    <script>
        // 初始化服务器状态显示
        initMinecraftServerChecker({
            containerId: 'minecraft-server-checker',
            serverConfig: {
                address: "<?php echo $serverAddress; ?>",
                port: <?php echo $serverPort; ?>,
                type: "<?php echo $serverType; ?>",
                apiUrl: "<?php echo $apiProxyUrl; ?>"
            }
        });

        // 显示提示信息
        function showToast(message, type = 'info', duration = 3000) {
            const existingToast = document.querySelector('.toast');
            if (existingToast) {
                existingToast.remove();
            }

            const toast = document.createElement('div');
            toast.className = 'toast';
            
            const colors = {
                info: '#00A3E8',
                success: '#55FF55',
                error: '#FF5555',
                warning: '#FFAA00'
            };
            toast.style.borderLeftColor = colors[type] || colors.info;
            
            toast.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideUp 0.3s ease reverse';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        // 检查是否可以下载（动态检查）
        async function checkCanDownload() {
            try {
                const response = await fetch('<?php echo $apiProxyUrl; ?>');
                if (!response.ok) return false;
                
                const data = await response.json();
                if (!data.success || !data.data.online) return true; // 服务器离线时可以下载
                
                const playersOnline = data.data.players?.online || 0;
                return playersOnline === 0;
            } catch (error) {
                console.error('检查下载状态失败:', error);
                return false;
            }
        }

        // 更新下载按钮状态
        function updateDownloadButton(canDownload, playersOnline = 0) {
            const downloadBtn = document.getElementById('downloadWorldBtn');
            const downloadStatus = document.getElementById('downloadStatus');
            
            if (!downloadBtn) return;
            
            if (canDownload) {
                downloadBtn.disabled = false;
                downloadBtn.classList.remove('blocked');
                downloadBtn.textContent = '下载存档';
                downloadStatus.classList.remove('show', 'online');
            } else {
                downloadBtn.disabled = true;
                downloadBtn.classList.add('blocked');
                downloadBtn.textContent = '有玩家在线，不可下载';
                downloadStatus.textContent = `⚠️ 当前有 ${playersOnline} 名玩家在线，请等待玩家下线后再下载存档`;
                downloadStatus.classList.add('show', 'online');
            }
        }

        // 定期检测玩家状态
        setInterval(async () => {
            const canDownload = await checkCanDownload();
            // 需要重新获取玩家数量
            try {
                const response = await fetch('<?php echo $apiProxyUrl; ?>');
                const data = await response.json();
                const playersOnline = (data.success && data.data.online) ? (data.data.players?.online || 0) : 0;
                updateDownloadButton(canDownload, playersOnline);
            } catch (e) {
                updateDownloadButton(canDownload, 0);
            }
        }, 5000); // 每5秒检查一次

        // 下载存档函数（动态下载，不跳转页面）
        async function downloadWorld() {
            // 先检查是否可以下载
            const canDownload = await checkCanDownload();
            if (!canDownload) {
                showToast('当前有玩家在线，无法下载存档', 'error');
                return;
            }
            
            const downloadBtn = document.getElementById('downloadWorldBtn');
            
            if (downloadBtn.disabled || downloadBtn.classList.contains('downloading')) {
                return;
            }
            
            downloadBtn.disabled = true;
            downloadBtn.classList.add('downloading');
            downloadBtn.textContent = '正在打包存档...';
            
            showToast('正在准备存档下载...', 'info', 5000);
            
            const progressContainer = document.createElement('div');
            progressContainer.className = 'progress-bar';
            progressContainer.id = 'downloadProgress';
            const progressFill = document.createElement('div');
            progressFill.className = 'progress-bar-fill';
            progressContainer.appendChild(progressFill);
            downloadBtn.parentNode.insertBefore(progressContainer, downloadBtn.nextSibling);
            
            fetch('download.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`下载失败 (${response.status})`);
                    }
                    
                    const contentDisposition = response.headers.get('Content-Disposition');
                    let filename = 'Bedrock_level.zip';
                    if (contentDisposition) {
                        const match = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                        if (match && match[1]) {
                            filename = match[1].replace(/['"]/g, '');
                        }
                    }
                    
                    const contentLength = response.headers.get('Content-Length');
                    let receivedLength = 0;
                    const reader = response.body.getReader();
                    const chunks = [];
                    
                    function read() {
                        return reader.read().then(({done, value}) => {
                            if (done) {
                                return chunks;
                            }
                            chunks.push(value);
                            receivedLength += value.length;
                            if (contentLength) {
                                const percent = (receivedLength / parseInt(contentLength)) * 100;
                                progressFill.style.width = percent + '%';
                            } else {
                                progressFill.style.width = '50%';
                            }
                            return read();
                        });
                    }
                    
                    return read().then(chunks => {
                        const allChunks = new Uint8Array(receivedLength);
                        let position = 0;
                        for (let chunk of chunks) {
                            allChunks.set(chunk, position);
                            position += chunk.length;
                        }
                        return { filename, data: allChunks };
                    });
                })
                .then(result => {
                    const blob = new Blob([result.data], { type: 'application/zip' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = result.filename;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                    
                    downloadBtn.disabled = false;
                    downloadBtn.classList.remove('downloading');
                    downloadBtn.textContent = '下载存档';
                    const progress = document.getElementById('downloadProgress');
                    if (progress) progress.remove();
                    
                    showToast('存档下载完成！', 'success');
                })
                .catch(error => {
                    console.error('下载失败:', error);
                    downloadBtn.disabled = false;
                    downloadBtn.classList.remove('downloading');
                    downloadBtn.textContent = '下载存档';
                    const progress = document.getElementById('downloadProgress');
                    if (progress) progress.remove();
                    showToast('下载失败: ' + error.message, 'error');
                });
        }
    </script>
</body>
</html>
