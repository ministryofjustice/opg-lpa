# 0008. Flask site security

Date: 2021-09-03

## Status

* Proposed (2021-10-11)

## Context

As we go live with the Flask part of service-front, we need to
ensure that this is secure enough, immediately for static pages
and ultimately for forms as and when the first form is rolled out

## Decision

We will follow OWASP guidelines to secure the flask site.
We will immediately do any obvious security protection.
We will scan the python code

### OWASP 2021 top 10 on flask site

A01:2021 Broken Access Control :
The initial release consists of static pages that are public. The first form will also be public. Ultimately, we aim to share session information with the php site. At this point tests will be required to ensure relevant content cannot be accessed without logging in.
Except for public resources, deny by default.  This does happen to some extent in that the flask site needs nginx to actively be configured to proxy_pass pages to it. However we need to be careful with any wildcard instructions to nginx.

A02:2021 Cryptographic failure, (formerly known as Sensitive Data Exposure).
We do not keep any secrets in the repository and any in use will be protected using secrets management services, and accessed by code from there.
We use security scans and precommit hooks to check for secrets, in order to reduce the chance of these being committed
see : [OPG security policy](https://docs.opg.service.justice.gov.uk/documentation/guides/security_process.html#security-in-our-process)

A03:2021 Injection (ths category now includes Cross Site Scripting)
This will be worth addressing when we have forms to submit. We already have some headers in place against this (see "Other considerations" section)

A04:2021 Insecure Design  ( New category for 2021  ):
This includes error messages that reveal too much info e:g a server's IP
Or code that has a variable saying whether a user is authenticated or not, that other code could fail to check
This will become more of an issue to check, as we develop the flask site further

A05:2021 Security Misconfiguration
Includes ports being open when they shouldn't, default config which is too open,
Default account and password, not an issue yet as we do not have authentication
Error handling reveals stack traces -  This is avoided by the fact that flask has development mode switched off by default. Stack traces will appear in the logs only, the user would be shown a 500 error
Should not install unnecessary features or frameworks. We base the flask container on a very basic docker image, not including irrelevant components

A06:2021 Vulnerable and outdated components
We should ensure versions of components are kept up to date.  On initial release, we have an older version of flask pinned, but we have a story to get all
the necessary components working with later flask, and ultimately stop pinning the version and use dependabot to keep components updated.

A07:2021  Identification and Authentication failures (formerly Broken Authentication) :
We aim to share session info with PHP , this will happen only when we get to forms that require authenticating to view.

A08:2021 Software and Data Integrity Errors (new for 2021)
We address this by ensuring the security of the Circle CI/CD pipeline

A09:2021 Security Logging and Monitoring failures (formerly insufficient logginng & monitoring)
While page access is automatically logged in nginx and by flask, when we do get to the point of having authenticated pages, we will need to log all login attempts

A10:2021  Server-side request forgery
This is addressed by restricting assets to our own servers, not pulling in anything external. In future we will allow a select few such as Google Analytics but it will still be tightly restricted.

### Other Considerations

CSRF - This is provided in the form framework Flask-WTForms and we make use of this
The question was asked, whether flask can uniwttingly expose any environment variables, but no discussion of this was to be found online.
We use Flask-Talisman to provide protection , including in the form of headers
It can be verified using Chrome inspector, that the following recommended headers are automatically set:  (2 more than is done for PHP site)
Content-Security-Policy and X-Content-Security-Policy is set to the default very strict. This means that only assets from the same domain can be served
We will later relax this to allow Google Analytics, there is a story on the backlog for this. These aren't currently set on the PHP site,  we also have a story
on the backlog for that.
X-Content-Type-Options to nosniff.  This forces browser to honour response content type instead of trying to guess it, which could leads to a cross-site-scripting attack  (done in PHP site too)
X-Frame-Options to SAMEORIGIN  - prevents external sites embedding the site in an invisible iframe - "clickjacking"  (done in PHP site too)
X-XSS-Protection set to 1; mode=block .  Tries to prevent XSS attacks by preventing page from loading if request contains something that looks like js, and the response contains the same data  (done in PHP site too)
strict-transport-security (use https) is set by nginx if not already there (for php), and set on flask

We don't ask users to upload files, therefore shouldn't be vulnerable to security issues around that yet, however this could become an issue in future for example Modernise requiring uploads of documents

We should generally use jinja2 rather than "hand-crank" html, as the jinja2 templates generate safety tested html

We should always quote attributes with quotes when using Jinja expressions in them, to prevent an attacker inserting custom javascript handlers

## Consequences

Cons:

* Following security standards can cause development to slow down and give us less options.
* More complex code as flask is not used just "out-of-the-box", some extra dependencies e:g csrf protect

Pros:

* More secure flask site.
