# Setting admin users

## instructions

admin users are allowed to access the admin service page, to pull user feedback and search for other users.
In order for them to have access, you will need to:

- log into the relevant aws account on the console, which has the right permissions to manage secrets.

- add them in CSV format via Secrets Manager in the secret below:

``` text
<account name>/opg_lpa_common_admin_accounts
```

 where `<account_name>` is `development`, `preproduction` or `production`

e.g.

```text
user1@example.com,user2@example.com
```
