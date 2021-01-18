// This makes a user number which will be used for all tests
// it needs to happen before startup rather than in support/index.js so
// that we have the same user number for all feature files
// This code is therefore called from start.sh.  
// For safety, for now we generate userNumber using 
// the exact same js code that the Casper tests did
var random = Math.floor(Math.random() * 999999999);

var date = new Date();
var userNumber = date.getTime() + "" + random;
console.log(userNumber);
