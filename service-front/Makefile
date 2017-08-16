.PHONY: watch
watch:
	docker run -it --rm -v $$(pwd):/app evolution7/nodejs-bower-grunt grunt watch --env=prod --gruntfile /app/Gruntfile.js

.PHONY: cs
cs:
	docker run -i --rm -v $$(pwd):/app registry.service.opg.digital/opguk/phpcs --standard=PSR2 --runtime-set ignore_warnings_on_exit true --runtime-set ignore_errors_on_exit true module/Application/src/

.PHONY: test
test:
	docker run -i --rm -v $$(pwd):/app registry.service.opg.digital/opguk/phpunit module/Application/tests --bootstrap module/Application/tests/Bootstrap.php

.PHONY: testcoverage
testcoverage:
	docker run -i --rm -v $$(pwd):/app registry.service.opg.digital/opguk/phpunit module/Application/tests --bootstrap module/Application/tests/Bootstrap.php
