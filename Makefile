SHELL := /bin/bash
CODECEPT_DEBUG_FLAG := $(if $(filter true yes 1 on,$(CODECEPT_DEBUG)),--debug,)

.PHONY: help
help: # Show help for each of the Makefile recipes.
	@grep -E '^[a-zA-Z0-9 -.]+:.*#'  Makefile | sort | while read -r l; do printf "\033[1;32m$$(echo make $$l | cut -f 1 -d':')\033[00m:$$(echo $$l | cut -f 2- -d'#')\n"; done

.PHONY: up
up: # Up local app
	docker compose up --build -d

.PHONY: down
down: ## Stops and removes the Docker containers, volumes, and images
	docker compose down --remove-orphans -v --rmi all

.PHONY: re-build
re-build: ## Rebuilds the Docker images by first stopping and removing the containers, volumes, and images, then building again
	$(MAKE) down
	$(MAKE) build

.PHONY: permissions
permissions: # Clean permission on cache, logs and tests output directories
	sudo chmod -f -R 777 symfony/var/cache/ || true
	sudo chmod -f -R 777 symfony/var/log/ || true
	sudo chmod -f -R 777 symfony/tests/_output || true
	sudo chmod -f -R 777 symfony/var/share/ || true
	sudo chown -f -R $$(id -u):$$(id -g) symfony/tests/_output || true
	sudo chown -f -R $$(id -u):$$(id -g) symfony/var || true
	sudo chown -f -R $$(id -u):$$(id -g) symfony/vendor || true

.PHONY: clean
clean: # Clear cache and remove log files and test results
	@echo "Cleaning cache and logs files..."
	sudo rm -rf symfony/var/cache/**/*
	sudo rm -f symfony/var/log/*
	sudo rm -f symfony/tests/_output/*.html symfony/tests/_output/failed

.PHONY: cache
cache: # Clear cache
	docker compose exec web bin/console cache:clear

.PHONY: dbload
dbload: # Load dev db
	docker compose exec web php -dxdebug.mode=off bin/console doctrine:schema:drop --force
	docker compose exec web php -dxdebug.mode=off bin/console doctrine:schema:update --force
	docker compose exec web php bin/console doctrine:fixtures:load --no-interaction --group=dev

test.dbload: # load test db
	docker compose exec web php -dxdebug.mode=off bin/console --env=test doctrine:schema:drop --force
	docker compose exec web php -dxdebug.mode=off bin/console --env=test doctrine:schema:update --force

.PHONY: codeception
codeception: # Run codeception tests
	make test.dbload
	make permissions
	docker compose exec --user $$(id -u):$$(id -g) web php -dxdebug.mode=off vendor/bin/codecept clean
	docker compose exec --user $$(id -u):$$(id -g) web php -dxdebug.mode=off vendor/bin/codecept build
	docker compose exec --user www-data:www-data web php -dxdebug.mode=off vendor/bin/codecept run $(suite) $(test)  $(CODECEPT_DEBUG_FLAG)

.PHONY: quality
quality: ## Runs code quality tools (PHP CS Fixer, PHP Code Sniffer, PHPStan, PHP Mess Detector)
	docker compose exec --user $$(id -u):$$(id -g) web composer phpcsfixer || true
	docker compose exec --user $$(id -u):$$(id -g) web composer phpcbf || true
	docker compose exec --user $$(id -u):$$(id -g) web composer phpcs || true
	docker compose exec --user $$(id -u):$$(id -g) web composer phpstan || true
	docker compose exec --user $$(id -u):$$(id -g) web composer phpmd || true
	
.PHONY: bash
bash: ## Opens a bash shell inside the web container
	docker compose exec -it web /bin/bash
