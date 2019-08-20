# Data access layer

This uses a repository pattern to isolate the Service layer from the concrete data store used.

The service layer should only ‘use’ items within `Application\Model\DataAccess\Repository
`.

All parameters sent to, and responses returned from, the repository layer will be either PHP primitives, or types defined in `Repository\`. Type hinting is used for *all* responses.

The actual concrete classes used are mapped in `Application\Module`.
