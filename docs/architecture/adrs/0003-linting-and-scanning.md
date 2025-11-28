# 0003. Linting and scanning

Date: 2021-09-03

## Status

* Proposed (2021-09-03)

## Context

Our code base is relatively large and complex, and was written
over several years by multiple contributors. This has resulted in
several issues:

* The style and approach varies considerably across it, sometimes
making it difficult to find the appropriate code, and often making
it tricky to format code so that it is easy to read and follow.
* Different developer preferences can result in
inconsistencies in style. For example, different editor setups could
introduce or remove trailing spaces as part of a commit. These
incidental edits increase the amount of churn in code files which
a developer has to review (e.g. if one developer's editor removes
trailing spaces, the diff for a commit may accidentally contains
dozens of irrelevant lines which just remove space).
* There is always the potential for a developer to accidentally commit
secret information to the code base, such as usernames and passwords.
While good practice has prevented this so far, we have no automated
gates to stop this happening.
* There are redundant and incorrect import statements, blocks of
code which are unreachable, ambiguous or redundant variable definitions
and the like, which potentially introduce bugs and vulnerabilities, and
generally increase maintenance overhead.

## Decision

We will gradually increase the quality of the code by introducing code scanning
and linting to address the above issues.

In the first instance, we'll implement the following:

* pre-commit hooks to check code before it is committed to the git repo
* github actions to regularly scan our code as part of builds

The tools we'll use follow the ad hoc standards used by other teams, as follows:

**pre-commit hooks**

* General file linting: pre-commit-hooks (trailing space removal; line end fixing)
* Secrets commit prevention: gitleaks; git-secrets
* PHP linting/automated fixes: pre-commit-php (php-cbf PHP Code
  Sniffer / Beautifier and Fixer with PSR-12 standard)
* Terraform linting: pre-commit-terraform (formatting and validation)

**github actions**

* codeql (code security scanning across all languages)
* gitleaks (check for secrets being committed)
* psalm (PHP code security scanning)
* trivy (container image scanning)
* trufflehog (detect credential leaks)
* tfsec (terraform security scanning)
* dependabot (to detect updates to composer and npm packages)

Note that github actions for code are currently focused on security scans *only*.
This is because the quality of our PHP code is especially suspect and would fail
more general lint scans. We will deal with this over time by manually
applying PHPStan and Psalm (to PSR-12 standard) to individual components to
bring them up to scratch.

As we add additional tools to these lists, we'll record the decisions for why we
included them.

When a linter fixes a file, we will isolate those changes (as far as possible)
from changes which relate to the issue being addressed, putting them in
a commit with a title something like "Fixes to satisfy PHP linter".

## Consequences

Cons:

* Linting PHP code whenever it changes adds overhead to simple edits. For example,
  introducing a one line change to a file which is several hundred lines long
  triggers the linter to check *all* of those lines. This may result in the linter
  requiring changes to dozens of lines (e.g. if lines are too long).

Pros:

* More secure code base.
* More consistent coding style.
* Making linting part of general development forces us to address outstanding
  issues with coding style which are unattractive to deal with separately.
