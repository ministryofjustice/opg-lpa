LPA Data Model Validation
===========

This document contains an explanation of LPA (in)validation responses.

Null
------------
If a value must be null, you'll receive the response:

`must-be-null`


Not Null & Blank
---------------
If a value cannot be blank/null, you'll receive the response:

- For Null: ``cannot-be-null``
- For Blank: ``cannot-be-blank``

It's also possible that one of two fields must not be null. In this instance you'll receive ``cannot-be-null``, however the path will refer to both fields. For example ``cannot-be-null`` with a path of ``address2/postcode`` means only one of address2 or postcode must not be null.


Type Validation
---------------
If a supplied value is of the incorrect type, you'll receive the response:

`expected-type:{type}`

where ``{type}`` is the expected data type or class. For example:

- ``expected-type:int``
- ``expected-type:xdigit`` (i.e. must be a hexadecimal value)
- ``expected-type:\Opg\Lpa\DataModel\Lpa\Payment\Payment``


Invalid value size (range or length)
--------------------------------------
If a supplied value is the too big/long or small/short, you'll receive the response:

- For too small/short: ``must-be-greater-than-or-equal:{limit}``
- For too big/long: ``must-be-less-than-or-equal:{limit}``

if a string much be exactly _N_ characters long, an invalid value will result in:

`length-must-equal:{N}`


DateTime
---------
All dates and times should be stored as a ``DateTime`` object with a UTC time zone. If they're not, you'll receive the response:

``expected-type:DateTime`` or ``timezone-not-utc``


Choice Values
--------------
Some properties require a string containing one of a set of predefined values. If an invalid value is passed, you'll receive the response:

``expected-values:{value0},{value1},{valueN}``

If a minimum number of values need selecting you'll receive:

`minimum-number-of-values:{limit}`

And if a maximum number of values need selecting you'll receive:

`maximum-number-of-values:{limit}`

Email Addresses
---------------
If a passed email address is invalid, you'll get:

`invalid-email-address`

Phone Number
------------
If a passed phone number is invalid, you'll get:

`invalid-phone-number`

Country
-------
If a passed country (code) is invalid, you'll get:

`invalid-country-code`
