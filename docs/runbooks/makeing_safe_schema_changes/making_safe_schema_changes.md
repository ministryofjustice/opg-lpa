# Making safe schema changes

Changes to the database schema can cause application downtime if not applied properly. This is because, during a deployment, both the old and new versions of the API container will be running at the same time and will expect different schemas.

The trick to safe schema changes is splitting them into multiple releases, and releasing them in the least disruptive order.

## Adding a new field or entity

You can generally add a new field or entity in a single release. The migration will be applied whilst the old container is running. Once the new container is released, it will start using the new field.

## Deleting a field

To safely delete a field you need to first do a release to remove it from code, then another to remove it from the database.

Your first release should remove code references to the field you're removing, and remove the field from the entity. However, it should **not** include a migration to drop the database column.

### Deleting an entity

You can delete an entity using the same technique as deleting a field, removing it from code first and subsequently adding a migration to drop it from the database.

Note though that you'll also need to account for relationships and foreign keys in your known differences, which may require extra caution.

## Altering a field

Field alterations vary in their risk and impact, so you'll need to carefully assess what the impact of your change will be and whether it needs to be split across multiple releases.

A particularly safe technique would be to create a _new_ field using the technique above, then delete the old one. If doing this, make sure to copy any existing data across to the new database column.

You can manually test the impact of altering a field by applying the database migration without changing the code. Your local environment will then act like the "old" containers during a release. However, this may not tell the full story as your local testing is limited in scope and scale.
