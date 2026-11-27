# Rotating the One Login signing key

The API signs its requests to GOV.UK One Login (the client assertion and the request object) with a private RSA key. One Login checks those signatures against the public keys we publish at `/.well-known/jwks.json`.

This runbook covers planned rotation in the integration accounts and in production, what to do if a key is leaked, and how to roll back.

## How it works

The keys are in one Secrets Manager secret per account, `<account>/onelogin-signing-keys`, holding JSON:

```json
{
  "current": "-----xxxxxxxxx-----\n...",
  "next": "-----xxxxxxxxx-----\n...",
  "previous": "-----xxxxxxxxx-----\n..."
}
```

- `current` is the key we sign with. It is required.
- `next` and `previous` are optional. Every key in the secret is published in the JWKS, but only `current` signs.
- Each key's ID (`kid`) is worked out from the key itself, so a new key always gets a new ID. Nothing to configure.
- Any other field, a non-RSA key or the same key in two slots is rejected, and One Login sign-in fails until it is fixed.

Why rotation is done in steps:

- One Login caches our public keys for up to 24 hours, but "will only call your JWKS endpoint if its cache does not contain a key that relates to a kid in a received JWT" ([How GOV.UK One Login works](https://docs.sign-in.service.gov.uk/how-gov-uk-one-login-works/)). So it picks up a new key ID as soon as we sign with it.
- The old key must stay published for at least 24 hours after we stop signing with it, while One Login may still hold requests or a cache that refer to it.

The API reads the secret itself and keeps it in memory for up to an hour. Each container has its own copy, so after changing the secret, force a new deployment of the API to pick it up straight away. Waiting an hour also works.

The secret is per account, not per environment, so every environment in that account (for example all the development environments) uses the same keys. Redeploy the API in each of them.

| Account | aws-vault profile | One Login environment |
| --- | --- | --- |
| development | `moj-lpa-dev` | integration |
| preproduction | `moj-lpa-preprod` | integration |
| production | `moj-lpa-prod` | production |

## Before you start

Work in a temporary directory, and delete it at the end. It will hold private keys.

```bash
PROFILE=moj-lpa-dev        # or moj-lpa-preprod, moj-lpa-prod
ACCOUNT=development        # or preproduction, production
SECRET="${ACCOUNT}/onelogin-signing-keys"

WORKDIR=$(mktemp -d) && cd "$WORKDIR"
```

```bash
# Save the secret's current value to keys.json
fetch_keys() {
  aws-vault exec "$PROFILE" -- aws secretsmanager get-secret-value \
    --secret-id "$SECRET" --query SecretString --output text > keys.json
}

# Check a new value before saving it: only the known slots, and a current key
check_keys() {
  if jq -e 'has("current") and (keys - ["current", "next", "previous"] == [])' "$1" > /dev/null; then
    echo "OK"
  else
    echo "NOT VALID - not saving it" >&2
    return 1
  fi
}

# Save a new value
put_keys() {
  aws-vault exec "$PROFILE" -- aws secretsmanager put-secret-value \
    --secret-id "$SECRET" --secret-string "file://$1"
}

# Force a new deployment of the API in every environment in the account
redeploy_api() {
  for cluster in $(aws-vault exec "$PROFILE" -- aws ecs list-clusters --query 'clusterArns[]' --output text); do
    aws-vault exec "$PROFILE" -- aws ecs update-service --cluster "$cluster" \
      --service api --force-new-deployment --query 'service.serviceName' --output text
  done
}

# The key IDs an environment publishes, current first
published_kids() {
  curl -s "$1/.well-known/jwks.json" | jq -r '.keys[].kid'
}
```

`redeploy_api` prints an error for any cluster without an `api` service. That's fine. Wait for the deployments to finish before checking the JWKS. Use the front URL of an environment with One Login enabled. For environments that aren't public, use the JWKS proxy URL that One Login is registered with.

## Planned rotation

Do this in development first, then preproduction, then production. In production, follow the usual change process and let the team know before each step.

### 1. Add the new key as `next`

```bash
fetch_keys
openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:2048 -out new.pem
jq --rawfile next new.pem '.next = $next' keys.json > step1.json
check_keys step1.json && put_keys step1.json
redeploy_api
```

Check that the JWKS now shows two key IDs, and that sign-in still works:

```bash
published_kids https://<front-url>
```

### 2. Switch signing to the new key

Once every environment in the account publishes both keys:

```bash
fetch_keys
jq '.previous = .current | .current = .next | del(.next)' keys.json > step2.json
check_keys step2.json && put_keys step2.json
redeploy_api
```

Check that the JWKS still shows two key IDs, with the new one first, and sign in to confirm it works.

### 3. After at least 24 hours, remove the old key

```bash
fetch_keys
jq 'del(.previous)' keys.json > step3.json
check_keys step3.json && put_keys step3.json
redeploy_api
```

Check that the JWKS shows one key ID, and sign in once more.

### Clean up

```bash
cd - && rm -rf "$WORKDIR"
```

## If a key is leaked

Removing a key from our JWKS does not stop One Login trusting it straight away, because it may have the key cached for up to 24 hours.

1. Contact One Login through the Live Service Hub ([their guidance](https://docs.sign-in.service.gov.uk/before-integrating/set-up-your-public-and-private-keys/#share-your-public-keys-using-a-jwks-endpoint)) and ask them to stop accepting the leaked key. Give them its key ID (from `published_kids`) and the client ID.
2. Replace every key in the secret with a new one:

   ```bash
   openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:2048 -out new.pem
   jq -n --rawfile current new.pem '{current: $current}' > emergency.json
   check_keys emergency.json && put_keys emergency.json
   redeploy_api
   ```

3. Check that the JWKS shows only the new key ID, and that sign-in works. One Login fetches our JWKS as soon as it sees the new key ID, so there's no wait.
4. Clean up the temporary directory, and raise an incident as usual.

## Rolling back a step

Secrets Manager keeps the previous value as the `AWSPREVIOUS` version. To go back to it:

```bash
aws-vault exec "$PROFILE" -- aws secretsmanager list-secret-version-ids --secret-id "$SECRET" \
  --query 'Versions[].[VersionId, join(`,`, VersionStages)]' --output text

aws-vault exec "$PROFILE" -- aws secretsmanager update-secret-version-stage --secret-id "$SECRET" \
  --version-stage AWSCURRENT --move-to-version-id <AWSPREVIOUS id> --remove-from-version-id <AWSCURRENT id>

redeploy_api
```

## One-off: moving to this secret

This was needed once per account when the API moved from the `<account>/onelogin-private-key` secret (a bare PEM passed in as an environment variable) to this one. The new secret was added in its own release first, because the pipeline deploys the API straight after the region Terraform and an empty secret would break One Login sign-in. Once that release had created the secret, this was run in development, preproduction and production, and only then was the API change merged.

```bash
aws-vault exec "$PROFILE" -- aws secretsmanager get-secret-value \
  --secret-id "${ACCOUNT}/onelogin-private-key" --query SecretString --output text > current.pem
jq -n --rawfile current current.pem '{current: $current}' > keys.json
check_keys keys.json && put_keys keys.json
cd - && rm -rf "$WORKDIR"
```

The key stays the same, but its key ID changes from `<account>-onelogin` to its thumbprint. One Login fetches our JWKS when it sees the new ID, so no wait is needed.
