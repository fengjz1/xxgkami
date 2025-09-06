#!/bin/bash

# 小小怪卡密验证系统 Docker 启动脚本

set -e

echo "🚀 正在启动小小怪卡密验证系统..."

# 检查 Docker 是否安装
if ! command -v docker &> /dev/null; then
    echo "❌ Docker 未安装，请先安装 Docker"
    exit 1
fi

# 检查 Docker Compose 是否安装
if ! docker compose version &> /dev/null; then
    echo "❌ Docker Compose 未安装，请先安装 Docker Compose"
    exit 1
fi

# 检查是否存在 .env 文件
if [ ! -f .env ]; then
    echo "📝 创建环境配置文件..."
    cp env.example .env
    echo "✅ 已创建 .env 文件，使用默认配置"
    echo "💡 如需修改配置，请编辑 .env 文件"
fi

# 创建必要的目录
echo "📁 创建必要的目录..."
mkdir -p docker/nginx/logs
mkdir -p docker/supervisor/logs

# 停止可能存在的旧容器
echo "🛑 停止旧容器..."
docker compose down 2>/dev/null || true

# 构建并启动服务
echo "🔨 构建并启动服务..."
docker compose up -d --build

# 等待服务启动
echo "⏳ 等待服务启动..."
sleep 10

# 检查服务状态
echo "🔍 检查服务状态..."
docker compose ps

# 显示访问信息
echo ""
echo "🎉 启动完成！"
echo ""
echo "📱 访问信息："
echo "   主应用: http://localhost"
echo "   管理后台: http://localhost/admin.php"
echo "   健康检查: http://localhost/health"
echo ""
echo "🔑 默认数据库信息："
echo "   数据库: xxgkami"
echo "   用户名: xxgkami_user"
echo "   密码: xxgkami_pass"
echo "   Root密码: root123456"
echo ""
echo "📋 常用命令："
echo "   查看日志: docker compose logs -f"
echo "   停止服务: docker compose down"
echo "   重启服务: docker compose restart"
echo ""
echo "⚠️  首次访问请先完成系统安装："
echo "   http://localhost/install/"
echo ""
