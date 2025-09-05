#!/bin/bash
set -e

# 注意：不再在容器内修改文件权限，避免影响宿主机上的git项目
# 文件权限应该通过Docker的user配置或宿主机的权限设置来处理
echo "启动核心服务..."

# 执行传递给脚本的原始命令 (即 Dockerfile 中的 CMD)
exec "$@"
