# Middleware and Listeners during Mezzio migration

Date: 2026-03-17

## Status

Accepted

## Context
We are in the process of migrating our application to Mezzio, which is a middleware-based framework. As part of this migration we started to move logic that was shared between controllers using inheritance to Listeners as well as writing a version of the logic that would be compatible with Mezzio/PSR7 middleware. Listeners carry out the following roles:

* `CurrentRouteListener` - adds the route name to event params (MVC path only; the equivalent in the middleware path is `RouteMatchMiddleware`)
* `AuthenticationListener` - ensures the user is authenticated and redirects to login if not
* `UserDetailsListener` - loads the user details from the database and adds them to the event params and session
* `LpaLoaderListener` - loads the LPA from the database and adds it to event params
* `TermsAndConditionsListener` - checks if the user has accepted the latest terms and conditions and redirects to the T&Cs page if not
* `ViewVariablesListener` - adds common variables to the view model to pages that dont' require an LPA
* `LpaViewInjectListener` - adds common variables to the view model for pages that require an LPA

* The downside of this approach was that we were only exercising the laminas code which increases the risk of something not working when we make the switch over. Additionally, while we have some controllers relying on the laminas-mvc event system we can't move to a middleware-only approach

`RouteMatchMiddleware` is a bridge between the laminas-mvc and Mezzio. When a `PipeSpec` route is matched by laminas-mvc, the framework places the laminas `RouteMatch` object on the PSR-7 request as an attribute. `RouteMatchMiddleware` reads that `RouteMatch`, extracts the matched route name and parameters (including route-level options such as `unauthenticated_route` and `allowIncompleteUser`), and re-exposes them as a Mezzio `RouteResult` attribute. This means every downstream middleware in the pipe can use the standard Mezzio `RouteResult::class` attribute regardless of whether it is running inside a laminas-mvc `PipeSpec` or a full Mezzio pipeline, making the eventual cut-over transparent.

## Decision
To ensure we are exercising the middleware code and to reduce the risk of something not working when we switch over to Mezzio, we have decided to use the laminas-mvc `PipeSpec` that supports PSR7 based middleware. This is configured as part of our routing config and is as close as we can get to Mezzio middleware.

Routes are configured using `RouteMiddlewareHelper::addMiddleware()` helper function, which builds a `PipeSpec` from the standard authenticated middleware stack minus any classes explicitly excluded:

```php
/**
 * Builds a PipeSpec with the standard authenticated middleware stack, minus any classes in $ignore.
 *
 * The default stack is:
 *   RouteMatchMiddleware → AuthenticationListener → UserDetailsListener
 *     → TermsAndConditionsListener → LpaLoaderMiddleware → $handlerClass
 *
 * @param string $handlerClass The handler to append at the end of the pipeline.
 * @param string[] $ignore Middleware classes to omit from the stack.
 */
function addMiddleware(string $handlerClass, array $ignore): PipeSpec
```

For an LPA-scoped route requiring the full stack:

```php
'life-sustaining' => [
    'type' => Literal::class,
    'options' => [
        'route'    => '/life-sustaining',
        'defaults' => [
            'controller' => PipeSpec::class,
            'middleware' => addMiddleware(LifeSustainingHandler::class, []),
        ],
    ],
],
```

For a non-LPA authenticated route (omitting `LpaLoaderMiddleware`):

```php
'change-password' => [
    'type'    => Literal::class,
    'options' => [
        'route'    => '/change-password',
        'defaults' => [
            'controller' => PipeSpec::class,
            'middleware' => addMiddleware(
                ChangePasswordHandler::class,
                [LpaLoaderMiddleware::class]
            ),
        ],
    ],
],
```

This will allow us to test and validate the middleware code while still maintaining compatibility with the laminas-mvc event system.

When migrating a controller to a handler we should always:
* Add `RouteMatchMiddleware`, `AuthenticationListener`, `UserDetailsListener` and `TermsAndConditionsListener` to routes that don't require an LPA to be loaded (pass `[LpaLoaderMiddleware::class]` as the ignore list)
* Add the above middleware and `LpaLoaderMiddleware` to routes that do require an LPA to be loaded (pass `[]` as the ignore list)
* Rely on objects already added to requests by the above middleware instead of re-querying the database for the same information in the handler
* Use `CommonTemplateVariablesTrait::getTemplateVariables()` in the handler to pass common view variables (user, session expiry, current route, LPA) to the template

## Consequences

- We will have a mix of middleware and listeners during the migration, which may lead to some confusion for developers who are new to the project.
- If a new handler added and it's route is configured without the Authentication middleware, there is potential for a security issue if the route is not protected by other means.
