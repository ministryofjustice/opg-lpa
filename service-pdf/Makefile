.PHONY: test
test:
	docker run -i --rm -v $$(pwd):/app registry.service.opg.digital/opguk/phpunit tests --bootstrap tests/Bootstrap.php


.PHONY: cs
cs:
	docker run -i --rm --user `id -u` -v $$(pwd):/app registry.service.opg.digital/opguk/phpcs --standard=PSR2 --runtime-set ignore_warnings_on_exit true --runtime-set ignore_errors_on_exit true src/

.PHONY: test
test:
	docker run -i --rm --user `id -u` -v $$(pwd):/app registry.service.opg.digital/opguk/phpunit tests -c tests/phpunit.xml

.PHONY: testcoverage
testcoverage:
	docker run -i --rm --user `id -u` -v $$(pwd):/app registry.service.opg.digital/opguk/phpunit tests -c tests/phpunit.xml --coverage-html tests/coverage/
