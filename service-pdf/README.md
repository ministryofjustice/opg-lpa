
# Lasting Power of Attorney PDF Service

The Lasting Power of Attorney PDF Service is responsible for generating pre-completed LPA forms as PDFs. It’s primarily designed so that it pulls document requests from a queue; then saves the resulting PDF into a cache, ready to be passed on back to the user.

## Testing

```sh
./vendor/bin/phpunit
```

This uses a PHP wrapper for the pdftk command. In some environments, this command may not perform correctly. In this case, it's recommended that you use the [Java pdftk implementation](https://gitlab.com/pdftk-java/pdftk) instead, as this matches what is used in the live service. Download [the jar file (listed as "Binary package")](https://gitlab.com/pdftk-java/pdftk/-/releases) and tell the tests to use it by setting the `PDFTK_COMMAND` environment variable, e.g.

```
PDFTK_COMMAND='java -jar pdftk-all.jar' ./vendor/bin/phpunit
```

It's also possible to get a coverage report (providing you have xdebug enabled in your PHP installation) with:

```
./vendor/bin/phpunit --coverage-html build
```

If you have XDebug set up so that you can use it for debugging in an IDE with `xdebug.mode=debug`, you may need to pass an additional environment variable to produce coverage reports:

```
XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-html build
```

## License

The Lasting Power of Attorney Attorney API Service is released under the MIT license, a copy of which can be found in [LICENSE](LICENSE).