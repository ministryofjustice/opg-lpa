# Lasting Power of Attorney Front End

The Lasting Power of Attorney front end makes up the user facing views and business logic which, together with our internal APIs, makes up the online Lasting Power of Attorney tool.

## Building assets

Static assets (JS and CSS) are built using [esbuild](https://esbuild.github.io/) via `build.js` and `build-css.sh`, orchestrated through `build.sh`.

First install Node dependencies:

```bash
cd service-front
npm ci --ignore-scripts
npm rebuild esbuild
```

Then run a full build:

```bash
npm run build
```

### What the build does

- **Handlebars templates** — HTML templates in `assets/js/lpa/templates/` are precompiled into `assets/js/lpa/lpa.templates.js`.
- **JavaScript** — all source files are concatenated in dependency order and minified into `public/assets/v2/js/application.min.js`. Individual scripts (session timeout, dashboard statuses, etc.) are minified separately into `public/assets/v2/js/opg/`.
- **CSS** — Sass files are compiled into `public/assets/v2/css/`.

### Individual build commands

| Command | What it does |
|---|---|
| `npm run build` | Full build (JS + CSS) |
| `npm run build:js` | JavaScript only |
| `npm run build:css` | CSS only |

### Watch mode (local development)

Watch mode rebuilds JS or CSS automatically whenever source files change. It requires [fswatch](https://github.com/emcornish/fswatch):

```bash
brew install fswatch
```

Then start watching:

```bash
npm run watch
```

When running the full local stack, `make dc-up` starts the containers and then runs `npm run watch` automatically, so rebuilt assets are immediately available in the browser without restarting anything.

## Tests

Unit tests can be found in `module/Application/tests/`.

End-to-end tests are in the `cypress/` directory at the root of the repository.

### Unit test coverage

To run the unit tests locally with coverage reporting:

1. Ensure your local PHP has [XDebug](https://xdebug.org/docs/install) installed.
2. Set `xdebug.mode = coverage` in your `php.ini`.
3. You may need to increase the default `memory_limit` in `php.ini`, e.g. to `1024M`.
4. Run the docker-compose stack first, which installs the Composer dependencies.

Then:

```bash
cd service-front
mkdir -p build/coverage
php vendor/bin/phpunit --coverage-html=build/coverage/
```

Coverage reports are available at `build/coverage/index.html`.

## Updating Composer dependencies

```bash
docker run --rm -v $(pwd)/service-front:/app composer:2 composer update --prefer-dist --no-interaction --no-scripts
```

Or use the Makefile helpers from the repo root:

```bash
make front-composer-update PACKAGE=vendor/package:version
```

## License

The Lasting Power of Attorney front end is released under the MIT license, a copy of which can be found in [LICENSE](LICENSE).
