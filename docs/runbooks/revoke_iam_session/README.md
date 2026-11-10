# Revoke a session for a user or role

We can end active console and other sessions by putting a deny all policy with a `TokenIssueTime` condition.

The script in this folder will put such a policy onto a user or role in an account.

## Revoke session policy

The following policy will terminate all current sessions. The user or role will be able to create new sessions.


```json
{
  "Version": "2012-10-17",
    "Statement": [
      {
        "Effect": "Deny",
            "Action": [
              "*"
            ],
            "Resource": [
              "*"
            ],
            "Condition": {
              "DateLessThan": {
                "aws:TokenIssueTime": "[policy creation time]"
                }
            }
        }
    ]
}
```

If you need to prevent a user from creating new sessions, consider deleting the user instead. See [delete-iam-user](../delete_user/README.md)

## Using the script

choose to revoke a session for a user or role with the mutually exclusive options `--revoke_user` or `--revoke_role`, then supply the name of the user or role with --name.

```shell
aws-vault exec identity-breakglass -- uv run python ./main.py --revoke_role --name test_role
```

```shell
aws-vault exec identity-breakglass -- uv run python ./main.py --revoke_user --name test.user
```
