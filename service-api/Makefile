.PHONY: test
test:
	docker run -i --rm -v $$(pwd):/app registry.service.opg.digital/opguk/phpunit module/Application/tests --bootstrap module/Application/tests/Bootstrap.php

.PHONY: testcoverage
testcoverage:
	docker run -i --rm -v $$(pwd):/app registry.service.opg.digital/opguk/phpunit module/Application/tests --bootstrap module/Application/tests/Bootstrap.php --coverage-html module/Application/tests/coverage/
