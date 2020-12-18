# Technical debt to pay back

* We added a new custom Logger to service-front, to enable us to log as JSON and log MVC error events in more detail. Consequently, we should remove opg-lpa-logger from service-front/composer.json, as it's no longer used. However, removing this and updating composer.lock causes the app to break. Need to investigate why that is and upgrade composer packages.
* Replace "abandoned" PHP libraries in composer.json files (as highlighted when running `composer update`).
* Use same nginx config file for admin-web, front-web and app-web. Currently the config files are separate but identical (I got as far as bringing them into line with each other).
* Remove service-front/module/Application/tests/functional (old functional tests which we never run?).
* Clean up content of service-front/docker, which appears to contain a lot of legacy files, e.g. beaver.d/, bin/, certificates/ (?).
* /home/ell/moj/opg-lpa/service-admin/docker/confd seems redundant.
* service-api/data/cache in root directory looks redundant.
* Make a /shared folder at top level. Move all the *-web docker files and local-ssl/ into there so we don't have duplication of Dockerfile and php-fpm conf files across service-front, service-admin and service-api.
