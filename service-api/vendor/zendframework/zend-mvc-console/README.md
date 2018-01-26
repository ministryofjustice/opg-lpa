# zend-mvc-console

[![Build Status](https://secure.travis-ci.org/zendframework/zend-mvc-console.svg?branch=master)](https://secure.travis-ci.org/zendframework/zend-mvc-console)
[![Coverage Status](https://coveralls.io/repos/zendframework/zend-mvc-console/badge.svg?branch=master)](https://coveralls.io/r/zendframework/zend-mvc-console?branch=master)

zend-mvc-console provides integration between:

- zend-console
- zend-mvc
- zend-router
- zend-view

and replaces the console functionality found in the v2 releases of the latter
three components.

- File issues at https://github.com/zendframework/zend-mvc-console/issues
- Documentation is at https://zendframework.github.io/zend-mvc-console/

## Installation

```console
$ composer require zendframework/zend-mvc-console
```

Assuming you are using the [component
installer](https://zendframework.github.io/zend-component-installer), doing so
will enable the component in your application, allowing you to immediately start
developing console applications via your MVC. If you are not, please read the
[introduction](https://zendframework.github.io/zend-mvc-console/intro/) for
details on how to register the functionality with your application.

## For use with zend-mvc v3 and up

While this component has an initial stable release, please do not use it with
zend-mvc releases prior to v3, as it is not compatible.

## Migrating from zend-mvc v2 console to zend-mvc-console

Please see the [migration guide](http://zendframework.github.io/zend-mvc-console/migration/v2-to-v3/)
for details on how to migrate your existing zend-mvc console functionality to 
the features exposed by this component.
