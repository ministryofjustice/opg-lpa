# 0002. Custom save handler in service-front

Date: 2021-09-02

## Status

* Proposed (2021-09-02)

## Context

The service-front component, written in PHP, uses the default Redis save
handler for persisting session data. In certain situations, the
application may request a resource *A* which takes significant time to deliver,
such as LPA statuses via the Sirius data API. If resource *A*
is requested via an Ajax request, it's possible that the client
will request a new resource *B* before *A* is fully processed. If processing for
*B* then completes before processing for *A*, the process for *A* can erroneously
overwrite session data added by *B*, resulting in loss of session data required
by *A*.

This causes particular problems for CSRF tokens, as shown by this typical sequence
on service-front:

1.  dashboard page loads in browser, triggering client-side Ajax request to statuses controller
2.  statuses controller reads session data **S** and initiates (slow) request to Sirius
    API to get LPA statuses
3.  meanwhile, user goes to replacement-attorney page; the Ajax request is now redundant, as the
    user isn't on the dashboard page any more, but the statuses controller doesn't know this
4.  replacement-attorney controller reads session data **S**
5.  statuses controller continues processing Sirius response, unaware of new data about to be added to
    session by replacement-attorney...
6.  replacement-attorney adds CSRF data to session, creating **S'**
7.  replacement-attorney page renders form with CSRF token, associated with data in **S'**
8.  replacement-attorney writes **S'** to session, including CSRF data
9.  statuses page finishes processing, unaware of **S'**; it assumes it has
    the correct data **S** and writes it to the session, losing the delta between
    **S** and **S'** (including the CSRF token!)
10. user submits form to replacement-attorney controller with CSRF token in the form
11. replacement-attorney controller loads again, but retrieves **S** from session (just written by
    statuses controller in 9); this doesn't have the CSRF token (which was in **S'**)
    for comparison with the value in the form submitted by the user; CSRF validation fails!

## Decision

Use a custom save handler to prevent certain Ajax requests from writing data to the session.
This will still use Redis as the storage back-end.

The approach is to send some Ajax requests with a custom `X-SessionReadOnly: true` header,
implying that the controller they invoke should only read from the session and never write to it.

The save handler inspects the header on the incoming request and ignores any requests to write
the session if accompanied by this header.

PHP 7+ provides a mechanism to only read from the session, via:

```
session_start(array('read_and_close' => true))
```

However, the complexity of the processing in the Laminas stack, which does its own session
management, overrides any attempts to call this function. Consequently, the pragmatic
solution is to move down the stack to the lower-level save handler, and implement the read-only
behaviour there for requests we know to be problematic.

## Consequences

Cons:

* Save handler code to maintain, as we can't just configure the default Redis save handler now.
* Additional configuration code to inject the save handler into the session.
* More complicated client-side requests as we have to decide which should only read from
  the session.
* Due to the complexity of session management, there is some additional risk that session handling
  is not implemented properly in the save handler. We implemented additional load tests to look
  for signs of sessions bleeding into each other to mitigate this.

Pros:

* Fixes the CSRF issue, as it prevents the race condition which causes Ajax requests to
  overwrite session state incorrectly.
