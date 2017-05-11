.PHONY: test
test:
	docker run -it --rm -v $$(pwd):/app registry.service.opg.digital/opguk/phpunit module/Application/tests --bootstrap module/Application/tests/Bootstrap.php
