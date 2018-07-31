# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.2.0 - 2018-04-30

### Added

- [#24](https://github.com/zendframework/zend-mvc-console/pull/24) adds support for PHP 7.1 and 7.2.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- [#24](https://github.com/zendframework/zend-mvc-console/pull/24) removes support for HHVM.

### Fixed

- [#21](https://github.com/zendframework/zend-mvc-console/pull/21) adds a missing import statement for `Zend\Router\RouteMatch` to the
  `ConsoleViewHelperManagerDelegatorFactory` class.

## 1.1.11 - 2016-08-29

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#11](https://github.com/zendframework/zend-mvc-console/pull/11) ups the
  minimum supported zend-mvc version to 3.0.3, to ensure that the
  `SendResponseListenerFactory` is present, fixing an issue with console output.
- [#15](https://github.com/zendframework/zend-mvc-console/pull/15) promotes
  `Zend\Mvc\Console\View\ViewManager::getView()` to public visibility,
  matching the API of `Zend\Mvc\View\Http\ViewManager`.

## 1.1.10 - 2016-05-31

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#8](https://github.com/zendframework/zend-mvc-console/pull/8) marks zend-mvc
  versions less than 3.0.0 as conflicts.

## 1.1.9 - 2016-05-31

### Added

- [#7](https://github.com/zendframework/zend-mvc-console/pull/7) adds support
  for handling any PHP 7 `Throwable`, not just `Exception`s, within the
  `RouteNotFoundStrategy` and `ExceptionStrategy`.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.1.8 - 2016-05-24

### Added

- Nothing.

### Deprecated

- The `ConsoleApplicationDelegatorFactory` is deprecated in favor of the
  `ViewManagerDelegatorFactory`, as the former does not work correctly in unit
  test situations, and the latter works correctly for both testing and in
  production usage.

### Removed

- Nothing.

### Fixed

- Re-maps the `ConsoleRouterDelegatorFactory` to
  `Zend\Router\RouteStackInterface` instead of `Router`, as the former is what
  zend-router now defines as the canonical service name; this change ensures the
  delegator factory intercepts correctly.

## 1.1.7 - 2016-05-24

### Added

- Adds `Zend\Mvc\Console\Service\ViewManagerDelegatorFactory`, which listens for
  the `ViewManager` service and, if in a console environment, returns the
  `ConsoleViewManager` service instead.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.1.6 - 2016-05-24

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Updates the `ConfigProvider::getDependencyConfig()` to add aliases for
  `console` and `Console`, targeting the `ConsoleAdapter` service. These were
  used internally, and were previously missing definitions.

## 1.1.5 - 2016-05-24

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Updates the `ConfigProvider::getDependencyConfig()` to remove the delegator
  entry for `ControllerPluginManager`, as the referenced delegator does not
  exist (it was never created, as plugins can be provided via configuration).

## 1.1.4 - 2016-05-24

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Updates the `ConfigProvider::getDependencyConfig()` to rename the key
  `delegator_factories` to `delegators` (as the latter is the key the service
  manager looks for).

## 1.1.3 - 2016-05-24

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Updates the zend-mvc constraint to allow using either current development
  versions of zend-mvc, or stable 3.0 releases once available.

## 1.1.2 - 2016-04-07

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- This release fixes development requirements to ensure tests can be executed.
- [#5](https://github.com/zendframework/zend-mvc-console/pull/5) fixes the
  `ConsoleExceptionStrategyFactory` to only inject an exception message if one
  was present in configuration; previously, it was overriding the default
  message with an empty string in such situations.

## 1.1.1 - 2016-03-29

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#4](https://github.com/zendframework/zend-mvc-console/pull/4) updates the
  code base to work with zendframework/zend-mvc@e1e42c33. As that revision (a)
  removes console-related functionality, and (b) removes routing functionality,
  it detailed further changes to this component required to ensure it runs
  correctly as a module.

## 1.1.0 - 2016-03-23

### Added

- [#3](https://github.com/zendframework/zend-mvc-console/pull/3) adds the
  `CreateConsoleNotFoundModel` controller plugin from zend-mvc. This also
  required adding `Zend\Mvc\Console\Service\ControllerPluginManagerDelegatorFactory`
  to ensure it is present in the controller plugin manager when in a console
  context.
- [#3](https://github.com/zendframework/zend-mvc-console/pull/3) adds
  `Zend\Mvc\Console\Service\ControllerManagerDelegatorFactory`, to add an
  initializer for injecting a console adapter into `AbstractConsoleController`
  instances.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#3](https://github.com/zendframework/zend-mvc-console/pull/3) updates the
  `AbstractConsoleController` to override the `notFoundAction()` and always
  return the return value of the `CreateConsoleNotFoundModel` plugin.
- [#3](https://github.com/zendframework/zend-mvc-console/pull/3) updates the
  `AbstractConsoleController` to mark it as abstract, as was always intended,
  but evidently never implemented, in zend-mvc.

## 1.0.0 - 2016-03-23

First stable release.

This component replaces the various console utilities in zend-mvc, zend-router,
and zend-view, and provides integration between each of those components and
zend-console.

While this is a stable release, please wait to use it until a v3 release of
zend-mvc, which will remove those features, to ensure everything works together
as expected.

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.
