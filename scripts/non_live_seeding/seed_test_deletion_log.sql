SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SELECT pg_catalog.setval('public.deletion_log_id_seq', 1, true);

/* elliot@townx.org */
INSERT INTO public.deletion_log (identity_hash, type, reason, "loggedAt") VALUES ('6f0428b7e7d66382ff16451474cd2349e75bb4edb850fd993eeb58411bf8f142827c9829b14cef9f5881e13bcb19316a497d1df4ca5e4f4c76f4648cf256d4e1', 'account-deleted', 'user-initiated', '2021-05-05 12:21:20.000000+00');

/* townxelliot@gmail.com */
INSERT INTO public.deletion_log (identity_hash, type, reason, "loggedAt") VALUES ('ae8a6aa0359e3ccb6168d1b0307cc3c0c9e6f08a16f31c12636733aff14ae6b3f3b9b76aa13f86010304d2ea4cae6f0fde814d1aaf0c6de8b49c3bcca757844c', 'account-deleted', 'user-initiated', '2021-05-06 14:50:10.172285+00');
