.PHONY: cs
cs:
	docker run -i --rm --user `id -u` -v $$(pwd):/app registry.service.opg.digital/opguk/phpcs --standard=PSR2 --runtime-set ignore_warnings_on_exit true --runtime-set ignore_errors_on_exit true module/Application/src/

.PHONY: test
test:
	docker run -i --rm --user `id -u` -v $$(pwd):/app registry.service.opg.digital/opguk/phpunit module/Application/tests -c module/Application/tests/phpunit.xml

.PHONY: testcoverage
testcoverage:
	docker run -i --rm --user `id -u` -v $$(pwd):/app registry.service.opg.digital/opguk/phpunit module/Application/tests -c module/Application/tests/phpunit.xml --coverage-html module/Application/tests/coverage/
