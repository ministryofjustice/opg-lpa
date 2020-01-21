
--
-- PostgreSQL database dump
--

-- Dumped from database version 9.6.11
-- Dumped by pg_dump version 10.4

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: opglpamaster
--
-- Test data, intentionally commited to repository
INSERT INTO public.users (id, identity, password_hash, activation_token, active, failed_login_attempts, created, updated, activated, last_login, last_failed_login, deleted, inactivity_flags, auth_token, email_update_request, password_reset_token, profile) VALUES ('082347fe0f7da026fa6187fc00b05c55', 'seeded_test_user@digital.justice.gov.uk', '$2y$10$C9QCpqBK/9xP7x04nUemhO.OvRc.AWCHOb/N0w8Z2SxOMfSnoNIMO', NULL, true, 0, '2020-01-21 15:15:11.007119+00', '2020-01-21 15:15:53+00', '2020-01-21 15:15:53+00', '2020-01-21 15:16:02+00', NULL, NULL, NULL, '{"token": "nJzAdgls7ALiyYJ7NhXL8IaDC2lLqdDnLtUuteS1TEC", "createdAt": "2020-01-21T15:16:02.000000+0000", "expiresAt": "2020-01-21T16:33:58.123695+0000", "updatedAt": "2020-01-21T15:18:58.000000+0000"}', NULL, NULL, '{"dob": {"date": "1982-11-28T00:00:00.000000+0000"}, "name": {"last": "User", "first": "Test", "title": "Mr"}, "email": {"address": "seeded_test_user@digital.justice.gov.uk"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}');


--
-- PostgreSQL database dump complete
--