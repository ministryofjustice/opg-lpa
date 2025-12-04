# 0005. Database access layer refactoring

Date: 2021-09-06

## Status

* Accepted (2021-09-06)

## Context

We recently discovered an issue with one of the classes in
`service-api/module/Application/src/Model/DataAccess/Postgres/`, where
a search parameter was not correctly escaped. On investigation,
we found that test coverage of the files in that directory was
practically zero.

We also found that writing unit tests for these classes was tricky, as
they were acting as factory classes for real database connections. This
made it difficult to mock out result sets etc. to test different
code paths.

In pseudo-code, the old code followed this pattern:

```
class AbstractBase
{
    // $adapter is a Laminas\Db\Adapter\Adapter;
    // this constructs AbstractBase with a reference
    // to a real database adapter
    public __construct($adapter)
    {
        $this->adapter = $adapter;
    }

    public getAdapter()
    {
        return $this->adapter;
    }
}

class UserData
{
    public getData()
    {
        $query = new Sql($this->getAdapter());
    }
}
```

Note that the `getData()` method is constructing the SQL directly
inside the `UserData` class. This means we can't mock it out
in a test context as we can't control how it's constructed.

## Decision

Refactor the `*Data` database access classes so that database-specific
operations and SQL construction can be mocked in a test context.

We decided to pull the adapter out of the data access
classes, instead wrapping it in a `DbWrapper` class. We then put some utility
methods on this class, such as a method for creating a `Sql` instance.
The `DbWrapper` then holds the reference to the adapter.

It looks like this:

```
class DbWrapper
{
    public __construct($adapter)
    {
        $this->adapter = $adapter;
    }

    public createSql()
    {
        return new Sql($this->adapter);
    }
}

class AbstractBase
{
    public __construct($dbWrapper)
    {
        $this->dbWrapper = $dbWrapper;
    }
}

class UserData
{
    public getData()
    {
        $query = $this->dbWrapper->createSql();
    }
}
```

We are now able to pass a mock `DbWrapper` instance to each data access
class; in turn, we can then return mock `Sql` instances from its
`createSql()` method. This will make it possible to unit test classes
which were previously very difficult to test.

## Consequences

Cons:

* Refactoring live code which has no existing automated tests is fraught with
  risk: any changes made could have potential repercussions which aren't
  immediately apparent. For example, the initial refactoring caused
  one of the daily cron jobs to fail (which generates statistics for the
  service).
* Additional classes to manage. However, these offer the potential for further
  refactoring, such as moving almost identical `SELECT` statement construction
  code from multiple classes into a single location.

Pros:

* Code can be unit tested. However, note that this will be a first pass at this
  issue, and the unit tests will be complex due to the sub-optimal structure
  of these classes (e.g. 6-7 mocks required for a single unit test);
  though these tentative tests should highlight additional refactoring we can perform
  in future.
