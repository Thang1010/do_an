#!/bin/bash
echo 'Đang tải code mới nhất từ GitHub...'
git pull origin main

echo 'Đang đóng gói và khởi động lại ứng dụng...'
docker compose up -d --build app

echo 'Xóa cache Laravel...'
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan view:clear

echo 'Cập nhật thành công!'
