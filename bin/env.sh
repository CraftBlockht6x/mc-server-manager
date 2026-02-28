#!/bin/bash
# Node.js/php 环境配置

# 基础路径
export PATH="/workspace/php:/workspace/node/bin:$PATH"
export NODE_PATH="/workspace/node/lib/node_modules"

# 修复 npm（如果链接不存在）
if [ ! -L "/workspace/node/bin/npm" ] || [ ! -e "/workspace/node/bin/npm" ]; then
    echo "修复 npm 链接..."
    cd /workspace/node/bin
    [ -f npm ] && mv npm npm.bak.$(date +%s) 2>/dev/null
    ln -s ../lib/node_modules/npm/bin/npm-cli.js npm
fi

# 创建 cli.js 兼容性链接
if [ ! -f "/workspace/node/lib/cli.js" ] && [ -f "/workspace/node/lib/node_modules/npm/bin/npm-cli.js" ]; then
    ln -s node_modules/npm/bin/npm-cli.js /workspace/node/lib/cli.js
fi

# 验证
echo "环境已加载: Node $(node --version), NPM $(npm --version)"