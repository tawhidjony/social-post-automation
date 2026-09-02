up:
	docker compose up -d
show-c:
	docker ps -a
show-i:
	docker images
php:
	docker exec -it tawhidjony-php bash
nginx:
	docker exec -it tawhidjony-nginx bash
build:
	docker compose build --no-cache
	docker compose up -d
	docker restart tawhidjony-nginx
down:
	docker compose down

rmic:
	docker stop $(shell docker ps -aq)
	docker rm $(shell docker ps -aq)
	docker rmi -f $(shell docker images -a -q)
	docker network prune -f
	docker volume rm $(shell docker volume ls -q)


# cmd

u:
	docker exec -it tawhidjony-php composer update
cp:
	docker compose up -d && docker exec -it tawhidjony-php cp .env.example .env
key:
	docker exec -it tawhidjony-php php artisan key:generate
n-i:
	docker exec -it tawhidjony-php npm install
n-build:
	docker exec -it tawhidjony-php npm run build
n-dev:
	docker exec -it tawhidjony-php npm run dev
reverb:
	docker exec -it tawhidjony-php php artisan reverb:start --debug
	
reverb-restart:
	docker exec -it tawhidjony-php php artisan reverb:restart
queue:
	docker exec -it tawhidjony-php php artisan queue:work

#migration
rl:
	docker exec -it tawhidjony-php php artisan route:list
rc:
	docker exec -it tawhidjony-php php artisan route:clear
#migration
mg:
	docker exec -it tawhidjony-php php artisan migrate
mgs:
	docker exec -it tawhidjony-php php artisan migrate --seed
mgrs:
	docker exec -it tawhidjony-php php artisan migrate:refresh --seed
