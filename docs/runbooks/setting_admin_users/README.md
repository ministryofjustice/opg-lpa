# Setting admin users

admin users are allowed to access the admin service page to pull feedback and search for other users.

In order to do this, you will need to add them in CSV format to the secret below the within the relevant aws account via Secrets Manager

e.g.

``` text
<account name>/opg_lpa_common_admin_accounts
```

where `<account_name>` is `development`, `preproduction` or `production`
