
# Lasting Power of Attorney API Service

The Lasting Power of Attorney API Service is responsible for managing and storing the details of LPAs made by users, and also for managing and storing users authentication details; plus the ability to authenticate against those details using either a email/password combination, or an authentication token. It is accessed via the Front End service.


## API Collections

To access the API DB the service requires four collections:
* **lpa**: Stores the details of the actual LPAs
* **user**: Users' 'About You' details. Does not hold email or password details.
* **lpaStats**: Pre-calculated cache of LPA stats
* **whoAreYou**: Holds responses to the Who Are You question. This has it's own collection as the results are anonymous.

#### Indexes

For _lpa_ collection:
```
- db.lpa.createIndex( { user: 1, updatedAt: -1 } )
- db.lpa.createIndex( { user: 1, search: "text" } )
- db.lpa.createIndex( { startedAt: 1 } )
- db.lpa.createIndex( { createdAt: 1 } )
- db.lpa.createIndex( { completedAt: 1 } )
```


## Auth Collections

To access the Auth DB the service requires two collections:
* **user**: Stores the details of the user.
* **log**: A log of past users. Details are hashed.

### Indexes

For _user_ collection:
```
- db.user.createIndex( { identity: 1 }, { unique: true, sparse: true } )
- db.user.createIndex( { last_login: 1 } )
- db.user.createIndex( { activation_token: 1 }, { unique: true, sparse: true } )
- db.user.createIndex( { inactivity_flags: 1 } )
- db.user.createIndex( { active: 1, created: 1 } )
- db.user.createIndex( { "auth_token.token": 1 }, { unique: true, sparse: true } )
- db.user.createIndex( { "password_reset_token.token": 1 }, { unique: true, sparse: true } )
- db.user.createIndex( { "email_update_request.token.token": 1 }, { unique: true, sparse: true } )
```

License
-------

The Lasting Power of Attorney Attorney API Service is released under the MIT license, a copy of which can be found in [LICENSE](LICENSE).
