#!/bin/bash

# 小小怪卡密验证系统 Docker 停止脚本

echo "🛑 正在停止小小怪卡密验证系统..."

# 停止所有服务
docker-compose down

echo "✅ 服务已停止"

# 询问是否清理数据
read -p "🗑️  是否清理所有数据（包括数据库）？这将删除所有数据！(y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "🧹 清理数据卷..."
    docker-compose down -v
    docker system prune -f
    echo "✅ 数据已清理"
else
    echo "💾 数据已保留"
fi

echo "👋 再见！"
