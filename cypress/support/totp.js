// Time-based one-time codes - same algorithm as GOV.UK One Login's example:
// https://github.com/govuk-one-login/onboarding-examples/blob/main/tools/totp/totp.ts
const crypto = require("crypto");

const STEP_SECONDS = 30;
const BASE32_ALPHABET = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567"; // pragma: allowlist secret

function base32Decode(secret) {
  const characters = secret.toUpperCase().replace(/[\s=-]/g, "");
  let bits = "";

  for (const character of characters) {
    const value = BASE32_ALPHABET.indexOf(character);
    if (value === -1) {
      throw new Error("The authenticator secret key is not valid base32");
    }
    bits += value.toString(2).padStart(5, "0");
  }

  const bytes = [];
  for (let i = 0; i + 8 <= bits.length; i += 8) {
    bytes.push(parseInt(bits.slice(i, i + 8), 2));
  }

  return Buffer.from(bytes);
}

function totp(secret, nowMs = Date.now()) {
  const counter = Buffer.alloc(8);
  counter.writeBigUInt64BE(BigInt(Math.floor(nowMs / 1000 / STEP_SECONDS)));

  const hmac = crypto.createHmac("sha1", base32Decode(secret)).update(counter).digest();
  const offset = hmac[hmac.length - 1] & 0x0f;
  const code = (hmac.readUInt32BE(offset) & 0x7fffffff) % 1000000;

  return code.toString().padStart(6, "0");
}

// Waits for the next code if this one is about to expire, so it is still valid when submitted
async function freshTotp(secret, minimumSecondsLeft = 5) {
  const secondsLeft = STEP_SECONDS - ((Date.now() / 1000) % STEP_SECONDS);

  if (secondsLeft < minimumSecondsLeft) {
    await new Promise((resolve) => setTimeout(resolve, secondsLeft * 1000 + 100));
  }

  return totp(secret);
}

module.exports = { totp, freshTotp };
