.PHONY: help build run stop restart logs migrate test clean

APP_NAME := wa-subtrack-bot
PORT := 8081

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

build: ## Build Docker image
	docker build -t $(APP_NAME) .

run: ## Run container
	docker run -d --name $(APP_NAME) --restart unless-stopped -p $(PORT):$(PORT) $(APP_NAME)

stop: ## Stop container
	docker stop $(APP_NAME)

restart: stop rm run ## Restart container

rm: ## Remove container
	docker rm -f $(APP_NAME)

logs: ## Show container logs
	docker logs -f $(APP_NAME)

logs-tail: ## Show last 50 lines
	docker logs --tail 50 $(APP_NAME)

migrate: ## Run migration inside container
	docker exec $(APP_NAME) php artisan migrate --force

tinker: ## Open tinker session
	docker exec -it $(APP_NAME) php artisan tinker

shell: ## Open shell inside container
	docker exec -it $(APP_NAME) sh

health: ## Check health endpoint
	@curl -s http://localhost:$(PORT)/api/health | python3 -m json.tool

deploy: build rm run ## Full rebuild + deploy
	@echo "✅ $(APP_NAME) deployed on port $(PORT)"

clean: ## Remove image and container
	docker stop $(APP_NAME) 2>/dev/null; docker rm $(APP_NAME) 2>/dev/null; docker rmi $(APP_NAME) 2>/dev/null; echo "cleaned"
