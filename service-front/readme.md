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
npm install

```

## To build grunt for development
- Apply the scss change
- Run below command

    ```docker run -it --rm -v $(pwd)/service-front:/srv huli/grunt:alpine grunt build --force```

## Tests

Some unit tests can be found in `opg-lpa-front/module/Application/tests/`

All other tests are located with the `lpa-deploy` repository.

With special thanks to [BrowserStack](https://www.browserstack.com) for providing cross browser testing.

## Development

### Updating composer libraries

    cd /app
    curl -s https://getcomposer.org/installer | php
    php composer.phar self-update
    php composer.phar update --prefer-dist --optimize-autoloader

## License

The Lasting Power of Attorney Data Models are released under the MIT license, a copy of which can be found in [LICENSE](LICENSE).

