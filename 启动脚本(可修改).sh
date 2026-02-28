#!/bin/bash
# 启动脚本(可修改).sh
# 用途：启动 Node.js API、PHP 服务器和 Minecraft 基岩版服务器，最后保持 bedrock_server 在前台
# 该程序适用于雨云MCSManager面板

# 加载环境变量（使 php 和 node 命令可用）
source /workspace/bin/env.sh

# 切换到工作目录
cd /workspace

echo "========================================"
echo "🚀 正在启动所有服务..."
echo "========================================"

# 1. 启动 Node.js API（后台）
echo "[1/3] 正在启动 Node.js API..."
cd mc-bedrock-api
# 如果已有进程，先停止（可选）
pkill -f "node.*mc-bedrock-api" 2>/dev/null
# 启动并将输出重定向到日志文件
npm run start > ../api.log 2>&1 &
API_PID=$!
cd ..
echo "   ✅ Node.js API 已启动，PID: $API_PID"
echo "      日志: /workspace/api.log"

# 2. 启动 PHP 服务器（后台）
echo "[2/3] 正在启动 PHP Web 服务器..."
cd web
pkill -f "php -S" 2>/dev/null
# 启动 PHP 内置服务器，监听所有接口的 8080 端口
php -S 0.0.0.0:8080 > ../php.log 2>&1 &
PHP_PID=$!
cd ..
echo "   ✅ PHP 服务器已启动，PID: $PHP_PID"
echo "      日志: /workspace/php.log"

# 3. 启动 Minecraft 基岩版服务器（前台）
echo "[3/3] 正在启动 Minecraft 基岩版服务器..."

# 检查 bedrock_server 是否存在且可执行
if [ ! -x "./bedrock_server" ]; then
    echo "❌ 错误：找不到可执行的 bedrock_server，请检查路径。"
    echo "   当前目录: $(pwd)"
    echo "   如果 bedrock_server 在其他位置，请修改脚本中的路径。"
    exit 1
fi

echo "========================================"
echo "✅ 所有服务已启动，Minecraft 服务器正在前台运行。"
echo "   按 Ctrl+C 停止 Minecraft 服务器（其他服务将继续后台运行）"
echo "========================================"

# 前台运行 bedrock_server（使用 exec 替换当前进程）
exec ./bedrock_server