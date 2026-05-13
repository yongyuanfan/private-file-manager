#! /user/local/bin/bash

# 进入项目目录
cd /app

# 判断vendor目录是否存在，不存在则执行composer安装依赖
if [ ! -d "vendor" ]; then
    composer install --no-dev --optimize-autoloader
fi

# 判断.env文件是否存在，不存在则复制环境变量配置文件
if [ ! -f ".env" ]; then
    cp .env.example .env
fi

# 启动服务
php start.php start
