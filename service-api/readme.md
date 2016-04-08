
# Lasting Power of Attorney API Service

The Lasting Power of Attorney API Service is responsible for managing and storing the details of LPAs made by users. It is accessed via the Front End service.


## Collections

The API service requires four collections:
* **lpa** - Stores the details of teh actual LPAs
* **user** - Users 'About You' details. Does not hold email or password details.
* **lpaStats** - Pre-calculated cache of LPA stats
* **whoAreYou** - Holds responses to the Who Are You question. This has it's own colelction as the results are anonymous.

### Indexes

For _lpa_ colelction:
```
- db.lpa.createIndex( { user: 1, updatedAt: -1 } )
- db.lpa.createIndex( { user: 1, search: "text" } )
- db.lpa.createIndex( { startedAt: 1 } )
- db.lpa.createIndex( { createdAt: 1 } )
- db.lpa.createIndex( { completedAt: 1 } )
```

License
-------

The Lasting Power of Attorney Attorney API Service is released under the MIT license, a copy of which can be found in [LICENSE](LICENSE).
