# Docker lifecycle
up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build --no-cache
	docker compose up -d
	docker restart tawhidjony-nginx

# Shell access
php:
	docker exec -it tawhidjony-php bash

nginx:
	docker exec -it tawhidjony-nginx bash

# Project setup
cp:
	docker compose up -d && docker exec -it tawhidjony-php cp .env.example .env

key:
	docker exec -it tawhidjony-php php artisan key:generate

u:
	docker exec -it tawhidjony-php composer update

# Frontend
n-i:
	docker exec -it tawhidjony-php npm install

n-build:
	docker exec -it tawhidjony-php npm run build

n-dev:
	docker exec -it tawhidjony-php npm run dev

dev-restart:
	docker exec tawhidjony-php rm -f public/hot
	docker compose restart php

# Background services
reverb:
	docker exec -it tawhidjony-php php artisan reverb:start --debug

reverb-restart:
	docker exec -it tawhidjony-php php artisan reverb:restart

queue:
	docker exec -it tawhidjony-php php artisan queue:work

# Artisan / DB
mg:
	docker exec -it tawhidjony-php php artisan migrate

mgs:
	docker exec -it tawhidjony-php php artisan migrate --seed

mgrs:
	docker exec -it tawhidjony-php php artisan migrate:refresh --seed

rl:
	docker exec -it tawhidjony-php php artisan route:list

rc:
	docker exec -it tawhidjony-php php artisan route:clear

# Docker info
show-c:
	docker ps -a

show-i:
	docker images

# Cleanup
rmic:
	docker stop $(shell docker ps -aq)
	docker rm $(shell docker ps -aq)
	docker rmi -f $(shell docker images -a -q)
	docker network prune -f
	docker volume rm $(shell docker volume ls -q)
