function initMinecraftServerChecker(config = {}) {
    const defaultConfig = {
        containerId: 'minecraft-server-checker',
        serverConfig: {
            address: "xiaohei.chaxil.top",
            port: 443,
            type: "bedrock",
            apiUrl: "http://localhost:8081/api/server/bedrock/xiaohei.chaxil.top?port=443"
        },
        displaySettings: {
            motd: true,
            version: true,
            players: true,
            gamemode: true,
            edition: true,
            software: true,
            plugins: true,
            delay: true,
            icon: true
        },
        refreshInterval: 3000,
        
        theme: {
            cardBackgroundColor: '#2D2D2D',
            textColor: '#F0F0F0',
            secondaryTextColor: '#A0A0A0',
            accentColor: '#00A3E8',
            successColor: '#55FF55',
            warningColor: '#FFAA00',
            errorColor: '#FF5555',
            borderColor: '#3D3D3D',
            primaryColor: '#2196F3',
            successButtonColor: '#4CAF50',
            warningButtonColor: '#FFC107',
            dangerButtonColor: '#F44336'
        }
    };

    const finalConfig = { ...defaultConfig, ...config };
    const {
        containerId,
        serverConfig,
        displaySettings,
        refreshInterval,
        theme
    } = finalConfig;

    const htmlContent = `
        <div id="${containerId}" class="minecraft-server-checker">
            <div class="server-list" id="server-list"></div>
        </div>
    `;

    const container = document.getElementById(containerId) || document.querySelector(`#${containerId}`);
    if (!container) {
        console.error(`未找到容器元素 #${containerId}`);
        return;
    }
    container.innerHTML = htmlContent;

    const style = document.createElement('style');
    style.textContent = `
        @font-face {
            font-family: 'Minecraft_English';
            src: url('Minecraft_English.otf');
            font-weight: normal;
            font-style: normal;
        }
        
        @font-face {
            font-family: 'Minecraft_Chinese';
            src: url('Minecraft_Chinese.ttf');
            font-weight: normal;
            font-style: normal;
        }
        
        .minecraft-server-checker {
            font-family: 'Minecraft_English', 'Minecraft_Chinese', monospace;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            color: ${theme.textColor};
        }

        .minecraft-server-checker .server-card {
            background-color: ${theme.cardBackgroundColor};
            border-radius: 8px;
            border: 1px solid ${theme.borderColor};
            padding: 20px;
            position: relative;
            transition: transform 0.2s ease;
        }

        .minecraft-server-checker .server-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }

        .minecraft-server-checker .server-header {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid ${theme.borderColor};
            gap: 15px;
        }

        .minecraft-server-checker .server-icon {
            width: 64px;
            height: 64px;
            image-rendering: pixelated;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            background-color: rgba(0, 0, 0, 0.2);
            flex-shrink: 0;
        }

        .minecraft-server-checker .server-icon[src=""],
        .minecraft-server-checker .server-icon[style*="display: none"] {
            display: none;
        }

        .minecraft-server-checker .server-header-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .minecraft-server-checker .server-name {
            font-size: 1.2rem;
            font-weight: bold;
            color: ${theme.textColor};
        }

        .minecraft-server-checker .server-status {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9rem;
            height: 24px;
            width: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .minecraft-server-checker .status-online {
            background-color: ${theme.successColor};
            color: #000;
        }

        .minecraft-server-checker .status-offline {
            background-color: ${theme.errorColor};
            color: #000;
        }

        .minecraft-server-checker .server-address {
            font-size: 0.9rem;
            color: ${theme.secondaryTextColor};
            margin-bottom: 15px;
        }

        .minecraft-server-checker .server-motd {
            background-color: rgba(0, 0, 0, 0.3);
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-family: 'Minecraft_English', 'Minecraft_Chinese', monospace;
            line-height: 1.6;
            border-left: 3px solid ${theme.accentColor};
        }

        .minecraft-server-checker .server-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }

        .minecraft-server-checker .info-item {
            display: flex;
            flex-direction: column;
        }

        .minecraft-server-checker .info-label {
            font-size: 0.8rem;
            color: ${theme.secondaryTextColor};
            margin-bottom: 5px;
        }

        .minecraft-server-checker .info-value {
            font-size: 1rem;
        }

        .minecraft-server-checker .info-value.green { color: ${theme.successColor}; }
        .minecraft-server-checker .info-value.orange { color: ${theme.warningColor}; }
        .minecraft-server-checker .info-value.red { color: ${theme.errorColor}; }
        .minecraft-server-checker .info-value.blue { color: ${theme.accentColor}; }

        .minecraft-server-checker .server-delay {
            background-color: rgba(255, 255, 255, 0.1);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .minecraft-server-checker .last-updated {
            font-size: 0.8rem;
            color: ${theme.secondaryTextColor};
            text-align: right;
            margin-top: 10px;
        }

        .minecraft-server-checker .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: ${theme.accentColor};
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);

    const serverList = document.getElementById('server-list');

    // 解码 HTML 实体
    function decodeHtmlEntities(str) {
        if (!str) return '';
        const textarea = document.createElement('textarea');
        textarea.innerHTML = str;
        return textarea.value;
    }

     async function init() {
        // 注册 Service Worker 以缓存字体
        await registerServiceWorker();
        
        renderServer();
        setInterval(refreshServer, refreshInterval);
    }

    // 注册 Service Worker
    async function registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('sw.js');
                console.log('[SW] 注册成功:', registration.scope);
                
                // 请求持久化存储权限
                if (navigator.storage && navigator.storage.persist) {
                    const isPersistent = await navigator.storage.persisted();
                    if (!isPersistent) {
                        const granted = await navigator.storage.persist();
                        console.log('[SW] 持久化存储权限:', granted ? '已授予' : '被拒绝');
                    }
                }
            } catch (error) {
                console.error('[SW] 注册失败:', error);
            }
        } else {
            console.log('[SW] 浏览器不支持 Service Worker');
        }
    }

    function renderServer() {
        serverList.innerHTML = '';
        const card = createServerCard(serverConfig);
        serverList.appendChild(card);
    }

    function createServerCard(server) {
        const card = document.createElement('div');
        card.className = 'server-card';

        card.innerHTML = `
            <div class="server-header">
                <span class="server-name">加载中...</span>
                <span class="server-status"><span class="loading"></span></span>
            </div>
            <div class="server-address">正在获取服务器信息...</div>
        `;

        checkServerStatus(server)
            .then(status => {
                updateServerCard(card, server, status);
            })
            .catch(error => {
                console.error(`无法获取服务器 ${server.address} 的状态`, error);
                updateServerCard(card, server, {
                    online: false,
                    error: error.message || "未知错误"
                });
            });

        return card;
    }

    function updateServerCard(card, server, status) {
        card.innerHTML = '';
        const now = new Date();
        const formattedTime = now.toLocaleString('zh-CN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        });

        // 头部容器
        const header = document.createElement('div');
        header.className = 'server-header';

        // 服务器图标
        if (displaySettings.icon && status.icon) {
            const iconImg = document.createElement('img');
            iconImg.src = status.icon;
            iconImg.alt = 'Server Icon';
            iconImg.className = 'server-icon';
            iconImg.onerror = function() {
                this.style.display = 'none';
            };
            header.appendChild(iconImg);
        }

        // 头部内容
        const headerContent = document.createElement('div');
        headerContent.className = 'server-header-content';

        const nameSpan = document.createElement('span');
        nameSpan.className = 'server-name';
        nameSpan.textContent = server.address;
        headerContent.appendChild(nameSpan);

        // 显示完整地址和端口
        const addressDiv = document.createElement('div');
        addressDiv.style.fontSize = '0.8rem';
        addressDiv.style.color = theme.secondaryTextColor;
        addressDiv.textContent = `${server.address}:${server.port} | ${server.type === 'bedrock' ? '基岩版' : 'Java版'}`;
        headerContent.appendChild(addressDiv);

        header.appendChild(headerContent);

        // 状态标签
        const statusSpan = document.createElement('span');
        statusSpan.className = `server-status ${status.online ? 'status-online' : 'status-offline'}`;
        statusSpan.textContent = status.online ? '在线' : '离线';
        header.appendChild(statusSpan);
        card.appendChild(header);

        if (status.online) {
            // MOTD 显示
            if (displaySettings.motd && status.motd) {
                const motdDiv = document.createElement('div');
                motdDiv.className = 'server-motd';
                
                let htmlContent = '';
                
                if (status.motd.raw) {
                    htmlContent = parseMinecraftColors(status.motd.raw);
                } else if (status.motd.html) {
                    let html = decodeHtmlEntities(status.motd.html);
                    html = html.replace(/\\n/g, '\n');
                    html = html.replace(/\n/g, '<br>');
                    htmlContent = html;
                } else if (status.motd.clean) {
                    htmlContent = status.motd.clean
                        .replace(/\\n/g, '\n')
                        .replace(/\n/g, '<br>');
                }
                
                motdDiv.innerHTML = htmlContent;
                card.appendChild(motdDiv);
            }

            // 延迟
            if (displaySettings.delay && status.latency !== undefined) {
                const delayDiv = document.createElement('div');
                delayDiv.className = 'server-delay';
                const delayClass = status.latency < 50 ? 'green' : status.latency < 100 ? 'orange' : 'red';
                delayDiv.innerHTML = `
                    <div class="info-label">延迟</div>
                    <div class="info-value ${delayClass}">${status.latency}ms</div>
                `;
                card.appendChild(delayDiv);
            }

            // 信息网格
            const infoDiv = document.createElement('div');
            infoDiv.className = 'server-info';

            if (displaySettings.version && status.version) {
                const versionItem = document.createElement('div');
                versionItem.className = 'info-item';
                const versionName = typeof status.version === 'object' ? status.version.name : status.version;
                versionItem.innerHTML = `
                    <div class="info-label">版本</div>
                    <div class="info-value blue">${versionName || '未知'}</div>
                `;
                infoDiv.appendChild(versionItem);
            }

            if (displaySettings.players && status.players) {
                const playersItem = document.createElement('div');
                playersItem.className = 'info-item';
                const online = status.players.online || 0;
                const max = status.players.max || 0;
                playersItem.innerHTML = `
                    <div class="info-label">玩家</div>
                    <div class="info-value green">${online}/${max}</div>
                `;
                infoDiv.appendChild(playersItem);
            }

            if (displaySettings.gamemode && status.gamemode) {
                const gamemodeItem = document.createElement('div');
                gamemodeItem.className = 'info-item';
                gamemodeItem.innerHTML = `
                    <div class="info-label">游戏模式</div>
                    <div class="info-value">${status.gamemode}</div>
                `;
                infoDiv.appendChild(gamemodeItem);
            }

            if (infoDiv.children.length > 0) {
                card.appendChild(infoDiv);
            }

            // 更新时间
            const updatedDiv = document.createElement('div');
            updatedDiv.className = 'last-updated';
            updatedDiv.textContent = `上次更新: ${formattedTime}`;
            card.appendChild(updatedDiv);
        } else {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'server-delay';
            errorDiv.innerHTML = `
                <div class="info-label">错误信息</div>
                <div class="info-value red">${status.error || '无法连接到服务器'}</div>
            `;
            card.appendChild(errorDiv);

            const updatedDiv = document.createElement('div');
            updatedDiv.className = 'last-updated';
            updatedDiv.textContent = `上次更新: ${formattedTime}`;
            card.appendChild(updatedDiv);
        }
    }

    async function checkServerStatus(server) {
        try {
            const response = await fetch(server.apiUrl);
            if (!response.ok) {
                throw new Error(`API返回错误: ${response.status}`);
            }

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || '查询失败');
            }

            return data.data;

        } catch (error) {
            console.error(`无法获取服务器 ${server.address} 的状态`, error);
            return {
                online: false,
                error: error.message || "无法连接到API服务器"
            };
        }
    }

    function parseMinecraftColors(text) {
        if (!text) return '';

        text = text.replace(/\\n/g, '\n').replace(/\\r/g, '');

        const colorMap = {
            '0': '#000000', '1': '#0000AA', '2': '#00AA00', '3': '#00AAAA',
            '4': '#AA0000', '5': '#AA00AA', '6': '#FFAA00', '7': '#AAAAAA',
            '8': '#555555', '9': '#5555FF', 'a': '#55FF55', 'b': '#55FFFF',
            'c': '#FF5555', 'd': '#FF55FF', 'e': '#FFFF55', 'f': '#FFFFFF',
        };

        const formatMap = {
            'k': 'font-family: monospace;',
            'l': 'font-weight: bold;',
            'm': 'text-decoration: line-through;',
            'n': 'text-decoration: underline;',
            'o': 'font-style: italic;',
            'r': 'reset'
        };

        const lines = text.split('\n');
        const resultLines = [];

        for (const line of lines) {
            if (line.trim() === '') {
                resultLines.push('<div class="motd-line">&nbsp;</div>');
                continue;
            }

            let result = '';
            let currentColor = '';
            let currentFormats = [];
            let buffer = '';

            for (let i = 0; i < line.length; i++) {
                if (line[i] === '§' && i + 1 < line.length) {
                    const code = line[i + 1].toLowerCase();

                    if (buffer.length > 0) {
                        let style = '';
                        if (currentColor) style += `color: ${currentColor};`;
                        if (currentFormats.length > 0) style += currentFormats.join('');
                        if (style) {
                            result += `<span style="${style}">${escapeHtml(buffer)}</span>`;
                        } else {
                            result += escapeHtml(buffer);
                        }
                        buffer = '';
                    }

                    if (colorMap[code]) {
                        currentColor = colorMap[code];
                    } else if (formatMap[code]) {
                        if (code === 'r') {
                            currentColor = '';
                            currentFormats = [];
                        } else {
                            currentFormats.push(formatMap[code]);
                        }
                    }

                    i++;
                } else {
                    buffer += line[i];
                }
            }

            if (buffer.length > 0) {
                let style = '';
                if (currentColor) style += `color: ${currentColor};`;
                if (currentFormats.length > 0) style += currentFormats.join('');
                if (style) {
                    result += `<span style="${style}">${escapeHtml(buffer)}</span>`;
                } else {
                    result += escapeHtml(buffer);
                }
            }

            if (result === '') result = '&nbsp;';
            resultLines.push(`<div class="motd-line">${result}</div>`);
        }

        return resultLines.join('');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    async function refreshServer() {
        const serverCard = document.querySelector('.server-card');
        if (!serverCard) return;

        try {
            const status = await checkServerStatus(serverConfig);
            updateServerCard(serverCard, serverConfig, status);
        } catch (error) {
            console.error('刷新服务器状态失败', error);
            updateServerCard(serverCard, serverConfig, {
                online: false,
                error: error.message || "刷新失败"
            });
        }
    }

    if (document.readyState !== 'loading') {
        init();
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
}