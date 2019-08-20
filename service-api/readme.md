
# Lasting Power of Attorney API Service

The Lasting Power of Attorney API Service is responsible for managing and storing the details of LPAs made by users, and also for managing and storing users authentication details; plus the ability to authenticate against those details using either a email/password combination, or an authentication token. It is accessed via the Front End service.


## Creating a new database migration
```
php vendor/bin/phinx create MigrationName
```
Will create a new migration script template under `db/migrations/`

Documentation for filling out the template can be found here: https://book.cakephp.org/3.0/en/phinx.html

To start the migration
```
php vendor/bin/phinx migrate
```

To rollback
```
php vendor/bin/phinx rollback
```



License
-------

The Lasting Power of Attorney Attorney API Service is released under the MIT license, a copy of which can be found in [LICENSE](LICENSE).
