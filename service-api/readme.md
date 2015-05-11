
Lasting Power of Attorney API Service
==============

##Required Indexes

- db.lpa.createIndex( { user: 1, updatedAt: -1 } )
- db.lpa.createIndex( { user: 1, search: "text" } )
- db.lpa.createIndex( { startedAt: 1 } )
- db.lpa.createIndex( { createdAt: 1 } )
- db.lpa.createIndex( { completedAt: 1 } )

- db.whoAreYou.createIndex( { who: 1, subquestion: 1, _id: 1  } )

License
-------

The Lasting Power of Attorney Attorney API Service is released under the MIT license, a copy of which can be found in ``LICENSE``.
