## —— ginsen/uri ——————————————————————————————————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

install: ## Install vendors according to the current composer.lock file
	composer install

update: ## Update vendors according to the current composer.json file
	composer update

test: phpunit.xml.dist ## Launch all unit tests
	bin/phpunit --stop-on-failure --testdox --no-coverage
