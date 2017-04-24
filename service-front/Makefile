.PHONY: watch
watch:
	docker run -it --rm -v $$(pwd):/app evolution7/nodejs-bower-grunt grunt watch --env=prod --gruntfile /app/Gruntfile.js

.PHONY: test
test:
	docker run -it --rm -v $$(pwd):/app phpunit/phpunit:5.5.0 module/Application/tests --bootstrap module/Application/tests/Bootstrap.php
