# 0006. Modernise the code base

Date: 2021-09-06

## Status

* Accepted (2021-09-06)

## Context

We have inherited a relatively large and complex legacy code base, mostly written in PHP.
PHP [appears to be on a downwards trend as a language](https://pypl.github.io/PYPL.html?country=GB),
especially in contrast with Python. It's likely it will become increasingly difficult
to find good PHP developers in future.

Anecdotally, PHP is not seen as a desirable language for developers to work with. It doesn't
have the cool factor of newer languages like golang; nor the clean syntax and API of
languages of similar pedigree, such as Python.

Our code base is also showing its age somewhat. Some of the libraries are starting to rot.
A mix of contractors and developers working on the code base over several years has
resulted in a mix of styles and approaches. While we have already cleared out a lot
of unused and/or broken code, there is likely to be more we haven't found yet.

We are also lagging behind the latest Design System guidelines, as our application was one
of the first to go live, before the current iteration of the Design System existed.
This means that any changes to design have to be done piecemeal and manually: we can't
simply import the newest version of the design system and have everything magically update.

This combination of factors means that the code base can be difficult to work with:
resistant to change and easy to break.

## Decision

We have decided to modernise the code base to make it easier to work with and better
aligned with modern web architecture and standards. This is not a small job, but
the guiding principles we've decided on, shown below, should help us achieve our aims.

("Modernising the code base" is not to be confused with "modernising LPAs". Here
we're just talking about modernising the code base for the Make an LPA tool.)

* **Don't rewrite everything at once**

  Where possible, migrate part of an application to a new
  component and split traffic coming into the domain so that some paths are diverted to that
  component. This will typically use nginx in dev, but may be done at the AWS level if
  appropriate (e.g in a load balancer or application gateway).

  This is challenging, but means that we don't have to do a "big bang" release of the new
  version of the tool. Our aim is to gradually replace existing components with new
  ones, which are (hopefully) simpler, future-proofed, more efficient, and don't rely on PHP.

* **Use Python for new work**

  We considered golang, but don't have the experience in the team to build applications with it.
  We felt that learning a new language + frameworks would only reduce our ability to deliver, with
  minimal benefits: our application is not under heavy load and responds in an
  acceptable amount of time, so golang's super efficiency isn't essential.
  We feel that we could scale horizontally if necessary and have not had any major issues
  with capacity in the past.

* **Choose containers or lambdas as appropriate**

  Use a container for components which stay up most of the time, and lambdas for
  "bursty" applications (e.g. background processes like PDF generation, daily statistics aggregation).

* **Choose the right lambda for the job**

  Use "pure" lambdas where possible. This is only the case where an application has simple dependencies
  which don't require unusual native libraries outside the
  [stock AWS Docker images for lambdas](https://gallery.ecr.aws/lambda/python)).

  If a component is problematic to run as a pure lambda, use a lambda running a Docker image based
  on one of the stock AWS Docker images for lambdas.

* **Choose the right Docker image**

  When using Docker images, prefer the following:
  * Images based on AWS Lambda images (if writing a component which will run as a Docker lambda).
  * Images based on Alpine (for other cases).
  * Images based on a non-Alpine foundation like Ubuntu, but only if an Alpine image is not available.

* **Use Flask and gunicorn**

  Use [Flask](https://flask.palletsprojects.com/) for new Python web apps, fronted by
  [gunicorn](https://gunicorn.org/) for the WSGI implementation.

* **Use the latest Design System**

  Use the [Government Design System](https://design-system.service.gov.uk/) guidelines for new UI. In
  particular, use the
  [Land Registry's Python implementation of the design system](https://github.com/LandRegistry/govuk-frontend-jinja),
  written as [Jinja2 templates](https://jinja.palletsprojects.com/).

  Our aim should be to utilise it without modification as far as possible, so that we can easily upgrade
  if it is changed by developers at the Land Registry.

* **Migrate legacy code to PHP 8**

  Where possible, upgrade PHP applications to PHP 8, when supported by [Laminas](https://getlaminas.org/).
  At the time of writing, Laminas support for PHP 8 is only partial, so we are stuck with PHP 7 for now,
  as large parts of our stack are implemented on top of Laminas.

* **Specify new APIs with OpenAPI**

  Specify new APIs using [OpenAPI](https://swagger.io/specification/). Ideally, use tooling
  which enables an API to be automatically built from an OpenAPI specification, binding to
  code only when necessary, to avoid repetitive boilerplate.

* **Controlled, incremental releases**

  Provision new infrastructure behind a feature flag wherever possible. This allows us to
  work on new components, moving them into the live environment as they are ready, but hidden
  from the outside world. When ready for delivery, we switch the flag over to make that piece
  of infrastructure live.

* **Follow good practices for web security**

  Be aware of the [OWASP Top Ten](https://owasp.org/www-project-top-ten/) and code to avoid those
  issues. Use tools like [Talisman](https://github.com/GoogleCloudPlatform/flask-talisman) to
  improve security.

* **Be mindful of accessibility**

  Consider accessibility requirements at every step of the design and coding phases. Aim to
  comply with [WCAG 2.1 Level AA](https://www.w3.org/WAI/WCAG22/quickref/) as a minimum. While the
  Design System helps a lot with this, always bear accessibility in mind when building workflows
  and custom components it doesn't cover.

* **Be properly open source**

  Make the code base properly open source. While our code is open, there are still barriers to entry
  for developers outside the Ministry of Justice, such as the requirement to have access to AWS secrets,
  S3, postcode API, the Government payment gateway and SendGrid for the system to work correctly. We
  will work towards removing these barriers so that onboarding of new developers (internally and
  externally) is seamless, and to enable potentially anyone to fully contribute to the project.

* **Improve test coverage everywhere**

  As we work on the code, be aware of gaps in testing and plug them as they arise. Don't wait for
  an opportunity to fix everything at once: make refactoring and adding unit tests part of the
  work on an issue (unless it's going to take longer than working on the issue!).

  Where a whole category of testing is missing, add it (for example, we
  have recently implemented the foundations for load testing; see
  [0004-implement-load-testing](./0004-implement-load-testing.md)).

* **Automate code quality metrics**

  Introduce tools to lint and scan code as we go, to ensure consistent, easy-to-follow code. See
  [0003-linting-and-scanning](./0003-linting-and-scanning.md)) for a starting point.

* **Peer review everything**

  All commits to the code base must go through peer review before merging. No lone wolf developers.

* **Be pragmatic**

  See the [pragmatic quick reference](https://www.ccs.neu.edu/home/lieber/courses/csg110/sp08/Pragmatic%20Quick%20Reference.htm)
  for a summary. These are generally good principles for software engineering.

## Consequences

Cons:

* As we replace components, or parts of components, the project will get even more complex. We will have
  to deal with authentication between different applications, marrying styles between applications
  in different languages/templates, sharing data between services and so on.

Pros:

* Over time, we should be able to retire our PHP applications, replacing them with ones in Python
  (or other languages). This will bring the benefits of a more modern code base which is nicer to
  work with and more maintainable long term.
