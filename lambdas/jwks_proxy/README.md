# JWKS Proxy

This exists to enable us to use One Login on environments that are not publicly
available. As we sign our initial authentication requests One Login needs to be
able to retrieve the relevant public key to check the signature. Instead of
exposing an environment entirely, we can use this Lambda to expose the keys.

The Lambda requires a single environment variable:

- `APP_JWKS_URL`: the URL within the app of the `jwks.json` data to proxy.
