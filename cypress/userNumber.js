// This makes a user number for tests.
// It needs to happen before startup rather than in support/index.js so
// that we have the same user number for all feature files under CI.
// This code is therefore called from start.sh.
// For safety, for now we generate userNumber using
// the exact same js code that the Casper tests did.
console.log("" + (new Date()).getTime() + Math.floor(Math.random() * 999999999));
