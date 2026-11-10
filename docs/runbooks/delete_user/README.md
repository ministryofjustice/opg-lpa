# Delete a user

The script in this folder will end any current sessions, delete all resources attached to a user and then delete the user from AWS.

If you need to prevent a user from creating new sessions, consider deleting the user instead. (see python/delete_user/main.py)

## Using the script

Provide the username for the user to be deleted with `--username`

```shell
aws-vault exec identity-breakglass -- uv run python ./main.py --username test.user
```
