SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET client_min_messages = warning;
SET row_security = off;

INSERT INTO public.users
       ( id, identity, active, created, updated, activated
       , profile
	     , one_login_sub, one_login_email)
VALUES ( '1234567812345678123456781234567a', 'invite-owner@example.com', true, NOW(), NOW(), NOW()
       , '{"dob": {"date": "1982-11-28T00:00:00.000000+0000"}, "name": {"last": "North", "first": "Frederick", "title": "Mr"}, "email": {"address": "ur-s3@example.com"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}'
	     , 'urn:fdc:mock-one-login:2023:h+1qsHVroEwCRQAfHlxlPBEXDDBE7SyVyOedj1l3tTI=', 'invite-owner@example.com');;

INSERT INTO public.shared_space
       ( id, name, created, updated)
VALUES ( '1234567812345678123456781234567b', 'My Space', NOW(), NOW() );

INSERT INTO public.shared_space_members
       ( "sharedSpaceId", "userId", created, "isAdmin", "isActive")
VALUES ( '1234567812345678123456781234567b', '1234567812345678123456781234567a', NOW(), TRUE, TRUE);

INSERT INTO public.shared_space_invites
       ( "sharedSpaceId", "invitedBy", "firstNames", "lastName"
       , email, "isAdmin", code, created, expires)
VALUES ( '1234567812345678123456781234567b', '1234567812345678123456781234567a', 'Test', 'User'
       , 'invite-valid@example.com', TRUE, '12345678', NOW(), NOW() + INTERVAL '7 days'),
       ( '1234567812345678123456781234567b', '1234567812345678123456781234567a', 'Test', 'User'
       , 'invite-deleted@example.com', TRUE, '12345678', NOW() - INTERVAL '31 days', NOW() - INTERVAL '24 days'),
       ( '1234567812345678123456781234567b', '1234567812345678123456781234567a', 'Test', 'User'
       , 'invite-expired@example.com', TRUE, '12345678', NOW() - INTERVAL '8 days', NOW() - INTERVAL '1 days'),
       ( '1234567812345678123456781234567b', '1234567812345678123456781234567a', 'Test', 'User'
       , 'invite-valid2@example.com', TRUE, '12345678', NOW(), NOW() + INTERVAL '7 days'),
       ( '1234567812345678123456781234567b', '1234567812345678123456781234567a', 'Test', 'User'
       , 'invite-deleted2@example.com', TRUE, '12345678', NOW() - INTERVAL '31 days', NOW() - INTERVAL '24 days'),
       ( '1234567812345678123456781234567b', '1234567812345678123456781234567a', 'Test', 'User'
       , 'invite-expired2@example.com', TRUE, '12345678', NOW() - INTERVAL '8 days', NOW() - INTERVAL '1 days');
