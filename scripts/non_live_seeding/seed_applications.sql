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
-- Data for Name: applications; Type: TABLE DATA; Schema: public; Owner: opglpamaster
--
-- Test data, intentionally commited to repository
INSERT INTO public.applications (id, "user", "updatedAt", "startedAt", "createdAt", "completedAt", "lockedAt", locked, "whoAreYouAnswered", seed, "repeatCaseNumber", document, payment, metadata, search) VALUES (33718377316, '082347fe0f7da026fa6187fc00b05c55', '2020-01-21 15:18:44.998797+00', '2020-01-21 15:16:28.827809+00', '2020-01-21 15:18:39.005026+00', '2020-01-21 15:18:58.194165+00', '2020-01-21 15:18:58.188918+00', true, true, NULL, NULL, '{"type": "property-and-financial", "donor": {"dob": {"date": "1982-11-28T00:00:00.000000+0000"}, "name": {"last": "User", "first": "Test", "title": "Mr"}, "email": {"address": "test_user@digital.justice.gov.uk"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}, "canSign": true, "otherNames": ""}, "preference": "", "instruction": "", "correspondent": {"who": "donor", "name": {"last": "User", "first": "Test", "title": "Mr"}, "email": {"address": "test_user@digital.justice.gov.uk"}, "phone": null, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}, "company": null, "contactByPost": false, "contactInWelsh": false, "contactDetailsEnteredManually": null}, "peopleToNotify": [{"id": 1, "name": {"last": "Person", "first": "Notifiable", "title": "Mr"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}], "primaryAttorneys": [{"id": 1, "dob": {"date": "1985-01-07T00:00:00.000000+0000"}, "name": {"last": "User", "first": "Celeste", "title": "Miss"}, "type": "human", "email": null, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}], "whoIsRegistering": "donor", "certificateProvider": {"name": {"last": "User", "first": "Francest", "title": "Dr"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}, "replacementAttorneys": [], "primaryAttorneyDecisions": {"how": null, "when": "now", "howDetails": null, "canSustainLife": null}, "replacementAttorneyDecisions": null}', '{"date": null, "email": null, "amount": 82, "method": "cheque", "reference": null, "gatewayReference": null, "reducedFeeLowIncome": null, "reducedFeeAwardedDamages": null, "reducedFeeUniversalCredit": null, "reducedFeeReceivesBenefits": null}', '{"instruction-confirmed": true, "people-to-notify-confirmed": true, "repeat-application-confirmed": true, "replacement-attorneys-confirmed": true}', 'Mr Test User');


--
-- PostgreSQL database dump complete
--
