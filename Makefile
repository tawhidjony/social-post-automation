up:
	docker compose up -d
certs:
	chmod +x .docker/nginx/certs/generate-certs.sh
	LOCAL_DOMAIN=$${LOCAL_DOMAIN:-social-post-automation.test} .docker/nginx/certs/generate-certs.sh
	docker compose restart nginx
mkcert-install:
	chmod +x .docker/bin/install-mkcert.sh
	.docker/bin/install-mkcert.sh
	@echo ""
	@echo "Next: make mkcert-ca-install && make certs-mkcert"
mkcert-ca-install:
	@MKCERT_BIN=$$(pwd)/.docker/bin/mkcert; \
	if [ ! -x "$$MKCERT_BIN" ] && command -v mkcert >/dev/null 2>&1; then MKCERT_BIN=$$(command -v mkcert); fi; \
	if [ ! -x "$$MKCERT_BIN" ]; then echo "Run make mkcert-install first" >&2; exit 1; fi; \
	echo "Installing mkcert local CA (sudo password required once)..."; \
	"$$MKCERT_BIN" -install
certs-mkcert:
	chmod +x .docker/nginx/certs/generate-certs.sh
	CERT_METHOD=mkcert LOCAL_DOMAIN=$${LOCAL_DOMAIN:-social-post-automation.test} .docker/nginx/certs/generate-certs.sh
	docker compose restart nginx
hosts:
	@echo "Add this line to /etc/hosts (requires sudo):"
	@echo ""
	@echo "  echo '127.0.0.1 social-post-automation.test' | sudo tee -a /etc/hosts"
	@echo ""
	@echo "Then visit: https://social-post-automation.test"
	@echo "HTTP redirect: http://social-post-automation.test:8080"
hosts-install:
	@grep -q 'social-post-automation.test' /etc/hosts && echo "Already in /etc/hosts" || echo '127.0.0.1 social-post-automation.test' | sudo tee -a /etc/hosts
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
