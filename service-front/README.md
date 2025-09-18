# Lasting Power of Attorney Front End

The Lasting Power of Attorney front end makes up the user facing views and business logic which, together with our internal APIs, makes up the online Lasting Power of Attorney tool.

## Building assets

Static assets are generated using grunt.

`grunt build`

To setup Grunt within the container
```bash
apt-get update && apt-get install nodejs-legacy npm ruby-sass

npm install -g grunt grunt-cli grunt-contrib-sass --save-dev

cd /app
npm ci --ignore-scripts

```

## To build grunt for development
- Apply the scss change
- Run below command

    ```docker run -it --rm -v $(pwd)/service-front:/srv huli/grunt:alpine grunt build --force```

## Tests

Some unit tests can be found in `opg-lpa-front/module/Application/tests/`

All other tests are located with the `lpa-deploy` repository.

With special thanks to [BrowserStack](https://www.browserstack.com) for providing cross browser testing.

### Test coverage

To run the unit tests locally with test coverage, install the necessary dependencies:

1. Ensure you local PHP has pecl (available as part of a [PEAR installation](https://pear.php.net/)).
1. [Install XDebug](https://xdebug.org/docs/install) for your local PHP.
2. Ensure your php.ini file has the following setting in it: `xdebug.mode = coverage`
3. You may need to increase the default PHP `memory_limit` in php.ini, e.g. to `1024M`.
4. Run the docker-compose stack, which installs the composer dependencies for the app.

With the above in place, you can run the unit tests with coverage reporting:

```
cd service-front
mkdir -p build/coverage
php vendor/bin/phpunit --coverage-html=build/coverage/
```

The coverage reports are available in *build/coverage/index.html*.

## Development

### Updating composer libraries

    cd /app
    curl -s https://getcomposer.org/installer | php
    php composer.phar self-update
    php composer.phar update --prefer-dist --optimize-autoloader

## License

The Lasting Power of Attorney Data Models are released under the MIT license, a copy of which can be found in [LICENSE](LICENSE).
