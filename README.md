# MC服务器查看工具

一个开箱即用的 Minecraft 基岩版服务器管理工具，集成了 Node.js API、PHP Web 前端和 Bedrock 服务器，专为雨云 MCSManager 容器环境设计。

## 特性
- 预编译 Node.js 和 PHP 环境，无需安装
- 后端 API 已预安装，即开即用
- 包含完整的 Web 管理界面
- 自动启动所有服务

## 快速开始

1. **确保在容器内**（已配置好环境变量）
2. **运行启动脚本**（MCSM面板点击“开启实例”）
   ```bash
   bash "启动脚本(可修改).sh"
   ```
3. 脚本将依次启动：
   - Node.js API（后台，日志 `api.log`）
   - PHP Web 服务器（后台，端口 8080，日志 `php.log`）
   - Minecraft 基岩版服务器（前台运行）

## 目录结构
```
/workspace/
├── bin/                 # 环境脚本
├── mc-bedrock-api/      # Node.js 后端 API
├── web/                 # PHP 前端
├── bedrock_server       # 基岩版服务器核心
├── api.log              # API 日志
├── php.log              # PHP 日志
└── 启动脚本(可修改).sh   # 主启动脚本
```

## 访问服务
- Web 管理界面：`http://容器IP:8080`
- Minecraft 服务器：默认端口 `19132`
- Node.js API：端口由 `mc-bedrock-api` 配置决定（通常为 3000）

## 注意事项
- 本程序**适用于雨云 MCSManager 容器**，宿主机环境需自行配置 PHP/Node
- 如需修改端口或启动参数，请编辑 `启动脚本(可修改).sh`
- 停止服务：Minecraft 服务器前台运行时按 `Ctrl+C` 即可停止（其他服务仍在后台）；如需完全停止，请手动结束相关进程

## 预装组件
- Node.js（已预编译）
- PHP 内置服务器
- 所有依赖已通过 `npm install` 安装完毕

如有问题，请检查日志文件 `api.log` 和 `php.log`。
