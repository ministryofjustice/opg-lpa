<?php

// Password committed to repo on purpose
$emailConfig = [
    'server' => '{imap.gmail.com:993/ssl}INBOX',
    'username' => getenv('CASPER_EMAIL_USER'),
    'password' => getenv('CASPER_EMAIL_PASSWORD'),
];
