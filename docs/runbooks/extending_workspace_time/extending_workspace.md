# Extending a PR workspace in development account

This process is used to extend the lifespan of a workspace; typically, an instance of the Make application to be used for UAT. This is useful for extended testing of complex PRs.
This is a simple process, which is done by extending the `ExpiresTTL` for a given workspace name via the WorkspaceCleanup Dynamo DB table.

steps:

1. Decide when you would like to extend the workspace's lifespan to.
2. Convert that date and time for expiry into a unix Epoch. A tool to help with this is <https://www.epochconverter.com/>
3. Open the Development Account console for AWS, assuming the operator role will be fine for this.
4. Search for `DynamoDB` in the top search bar and hit `<enter>`.
6. In the `Find tables by name` search box type `WorkspaceCleanup`. Click the name `WorkspaceCleanup` and then explore items.
5. Click `Explore table items`. There will be a list of tables to select.
7. Look for the workspace name of your environment. to look for this:

   - Either: On your PR CI build, check the`post_environment_domains` job; the value is in the last step `print deployment values` next to `Terraform workspace:`
   - Or: list out these locally using `aws-vault exec identity -- terraform workspace list` and ascertain one that matches your PR.

8. Click on the workspace name link.
9. For the Attribute name `ExpiresTTL` place the converted Epoch time from step 2 into the Value box.
10. Click `Save Changes`
11. You now have extended the lifespan (Time To Live) for the workspace.

## Limitations of approach

it is important to understand the workspace will *only* available until:

- the TTL expires by itself.
- Someone pushes a change to the PR, which will reset to the standard 24 hours.

The environment will have until the next cleanup cycle, at which point it will be destroyed - i.e. 6am / 6pm UTC daily.
Following the instructions for editing the workspace Time To Live will put this back in place.

## If no matching workspace record is present

This will not work if there is no record in place.
At this point it's recommended to create the PR environment through the normal CI Process of bumping the PR via a code change or merge.

## removing workspace
When manual testing is completed on UAT, it might be a good idea to remove the environment so that we're being more efficient with the cost.
 The simplest way to remove an environment is to:

1. locate the record as per steps 3 to 7 for changing Time to Live
2. Select the workspace required for deletion
3. click on `Actions` dropdown and select `Delete items`
4. At the confirmation click `Delete`
5. The cleanup job will destroy the environment up on the next clean up run.

However, should you need to do an immediate destruction of the workspace, you can do this from your local shell:

1. navigate to the `terraform/environment` folder of this repo
2. run `aws-vault exec identity -- terraform workspace select <workspace to destroy>`
3. run `aws-vault exec identity -- terraform destroy`
4. if happy type `yes` and hit `<enter>`
