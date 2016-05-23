# zf-config-dump
A Zend Framework 2 module that provides a simple method for dumping an application's current configuration array to the commend line.

This is useful when some of the application’s configuration is set via environment variables, as is common when using container services.

## Installing

zf-config-dump can be installed with [Composer](https://getcomposer.org/). Run this command:

```sh
composer require nsmithuk/zf-config-dump
```

Then you need to add `ZFConfigDump` to your _config/application.config.php_ file under the `modules` key.

## Usage and example output

Once installed, call the following to get the whole config output:
```sh
php public/index.php dump-config
```
![Example output](examples/full.png)

If you only want to return the config for a specific key, you can add a filter. For example, to just return the config for your database, you can call:
```sh
php public/index.php dump-config database
```
![Example output](examples/database.png)

You can also use dot notation to access deeper keys within the config. For example, to access the value set for just the database adapter:
```sh
php public/index.php dump-config database.adapter
```
![Example output](examples/adapter.png)