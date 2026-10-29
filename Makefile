#!/bin/bash

UID = $(shell id -u)
DOCKER_BE = node-fac-cob

help: ## Show this help message
	@echo 'usage: make [target]'
	@echo
	@echo 'targets:'
	@egrep '^(.+)\:\ ##\ (.+)' ${MAKEFILE_LIST} | column -t -c 2 -s ':#'

build: ## Rebuilds all the containers
	userid=${UID} docker-compose build

up: ## Up the containers
	userid=${UID} docker-compose up -d

stop: ## Stop the containers
	userid=${UID} docker-compose stop

rebuild: ## Restart the containers
	$(MAKE) stop && $(MAKE) build

up-prod: ## Up in prod enviroment
	userid=${UID} docker-compose -f docker-compose.prod.yml up -d

stop-prod: ## Stop prod containers
	userid=${UID} docker-compose -f docker-compose.prod.yml stop

# Backend commands
composer-install-nointeraction: # Installs composer dependencies
	userid=${UID} docker exec --user ${UID} ${DOCKER_BE} composer install --no-interaction

bash: ## bash into the be container
	userid=${UID} docker exec -it --user ${UID} ${DOCKER_BE} bash

code-style: ## Runs php-cs to fix code styling following Symfony rules
	userid=${UID} docker exec --user ${UID} ${DOCKER_BE} php-cs-fixer fix src --rules=@Symfony

ssh-schema-update: ## update database whit doctrine
	userid=${UID} docker exec -it --user ${UID} ${DOCKER_BE} sh scripts/resources/doctrine-drop-update-fill.sh
# End backend commands

# webserver dev
webserver-run: ## starts the Symfony development server
	userid=${UID} docker exec -it --user ${UID} ${DOCKER_BE} symfony serve -d

webserver-stop: ## stop the Symfony development server
	userid=${UID} docker exec -it --user ${UID} ${DOCKER_BE} symfony server:stop

webserver-logs: ## Show Symfony logs in real time
	userid=${UID} docker exec -it --user ${UID} ${DOCKER_BE} symfony server:log
