up:
	docker compose up -d

down:
	docker compose down

logs:
	docker compose logs -f --tail=200

install-backend:
	docker compose exec backend composer install

install-frontend:
	docker compose exec frontend npm install

install-websocket:
	docker compose exec websocket npm install

backend-key:
	docker compose exec backend php artisan key:generate

migrate:
	docker compose exec backend php artisan migrate --force

bootstrap: install-backend install-frontend install-websocket backend-key migrate
