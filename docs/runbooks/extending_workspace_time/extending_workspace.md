# Extending a PR workspace in development account

This process is used to extend the lifespan of a workspace; typically, an instance of the Make application to be used for UAT. This is useful for extended testing of complex PRs.
This is a simple process, which is done by extending the `ExpiresTTL` for a given workspace name via the WorkspaceCleanup Dynamo DB table.

steps:

1. Convert your desired date and time for expiry into a unix Epoch. A tool to help with this is <https://www.epochconverter.com/>
2. Open the Development Account console for AWS, assuming the operator role will be fine for this.
3. Search for `DynamoDB` in the top search bar and hit `<enter>`.
4. Look for `Tables` on the left hand side and click `Explore Items`. there will be a list of tables to select.
5. in the `Find tables by name` search box type `WorkspaceCleanup` and click the radio button next to the table name.
6. Look for the workspace name of your environment. to look for this:

   - Either: On your PR CI build, check the`post_environment_domains` job; the value is in the last step `print deployment values` next to `Terraform workspace:`
   - Or: list out these locally using `aws-vault exec identity -- terraform workspace list` and ascertain one that matches your PR.

7. Click on the workspace name link.
8. For the Attribute name `ExpiresTTL` place the converted Epoch time from step 1 into the Value box.
9. Click `Save Changes`
10. You now have extended the period of time for the workspace.

## Limitations of approach

it is important to understand this will *only* be in place until:

- the TTL expires by itself.
- Someone pushes a change to the PR, which will reset to the standard 24 hours.

The environment will have until the next cleanup cycle before destruction takes place - i.e. 6am / 6pm UTC daily.
Following the above will put this back in place.

## If no record is present

This will not work if there is no record in place.
At this point it's recommended to create the PR environment through the normal CI Process of bumping the PR via a code change or merge.
