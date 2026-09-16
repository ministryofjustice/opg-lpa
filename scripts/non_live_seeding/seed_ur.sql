-- Scenario 1 and 5
--
-- 1. Sign in using One Login random option
-- 2. Fail to link Make account (ur-s1-u1@example.com or ur-s5-linked@example.com / Pass1234)
-- 3. Successfully link Make account (ur-s1-u2@example.com or ur-s5-u2@example.com / Pass1234)

INSERT INTO public.users
       ( id, identity, active, created, updated, activated
       , password_hash
       , profile
	     , one_login_sub, one_login_email)
VALUES ( 'a1b10000000000000000000000000000', 'ur-s1-linked@example.com', true, NOW(), NOW(), NOW()
       , NULL
       , '{"dob": {"date": "1982-11-28T00:00:00.000000+0000"}, "name": {"last": "Walpole", "first": "Robert", "title": "Mr"}, "email": {"address": "ur-s1-linked@example.com"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}'
	     , 'urn:fdc:mock-one-login:2023:9rVopjd54JSNQNfXhLgMvJoGfWKtu6hACB52KTVKeuY=', 'ur-s1-linked@example.com'),
       ( 'a1b20000000000000000000000000000', 'ur-s1-unlinked@example.com', true, NOW(), NOW(), NOW()
       , '$2y$10$C9QCpqBK/9xP7x04nUemhO.OvRc.AWCHOb/N0w8Z2SxOMfSnoNIMO'
       , '{"dob": {"date": "1982-11-28T00:00:00.000000+0000"}, "name": {"last": "Compton", "first": "Spencer", "title": "Mr"}, "email": {"address": "ur-s1-unlinked@example.com"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}'
       , NULL, NULL),
       ( 'a5b10000000000000000000000000000', 'ur-s5-linked@example.com', true, NOW(), NOW(), NOW()
       , NULL
       , '{"dob": {"date": "1982-11-28T00:00:00.000000+0000"}, "name": {"last": "Pelham", "first": "Henry", "title": "Mr"}, "email": {"address": "ur-s5-linked@example.com"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}'
	     , 'urn:fdc:mock-one-login:2023:VvvshQovrt2/sQL15S3YsUvMPtctogpnAwii1Lm5gok=', 'ur-s5-linked@example.com'),
       ( 'a5b20000000000000000000000000000', 'ur-s5-unlinked@example.com', true, NOW(), NOW(), NOW()
       , '$2y$10$C9QCpqBK/9xP7x04nUemhO.OvRc.AWCHOb/N0w8Z2SxOMfSnoNIMO'
       , '{"dob": {"date": "1982-11-28T00:00:00.000000+0000"}, "name": {"last": "Cavendish", "first": "William", "title": "Mr"}, "email": {"address": "ur-s5-unlinked@example.com"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}'
       , NULL, NULL);

INSERT INTO public.applications
       ( id, "user", "updatedAt", "startedAt", "createdAt", "completedAt", "lockedAt"
       , locked, "whoAreYouAnswered", seed, "repeatCaseNumber", search
       , document
       , payment
       , metadata)
VALUES ( 3142811, 'a1b20000000000000000000000000000', NOW(), NOW(), NOW(), NOW(), NOW()
       , true, true, NULL, NULL, 'Mr Test User'
       , '{"type": "property-and-financial", "donor": {"dob": {"date": "1982-11-28T00:00:00.000000+0000"}, "name": {"last": "User", "first": "Another", "title": "Mr"}, "email": {"address": "test_user@digital.justice.gov.uk"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}, "canSign": true, "otherNames": ""}, "preference": "", "instruction": "", "correspondent": {"who": "donor", "name": {"last": "Brock", "first": "Tommy", "title": "Mr"}, "email": {"address": "test_user@digital.justice.gov.uk"}, "phone": null, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}, "company": null, "contactByPost": false, "contactInWelsh": false, "contactDetailsEnteredManually": null}, "peopleToNotify": [{"id": 1, "name": {"last": "Person", "first": "Notifiable", "title": "Mr"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}], "primaryAttorneys": [{"id": 1, "dob": {"date": "1985-01-07T00:00:00.000000+0000"}, "name": {"last": "User", "first": "Celeste", "title": "Miss"}, "type": "human", "email": null, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}], "whoIsRegistering": "donor", "certificateProvider": {"name": {"last": "User", "first": "Francest", "title": "Dr"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}, "replacementAttorneys": [], "primaryAttorneyDecisions": {"how": null, "when": "now", "howDetails": null, "canSustainLife": null}, "replacementAttorneyDecisions": null}'
       , '{"date": null, "email": null, "amount": 82, "method": "cheque", "reference": null, "gatewayReference": null, "reducedFeeLowIncome": null, "reducedFeeAwardedDamages": null, "reducedFeeUniversalCredit": null, "reducedFeeReceivesBenefits": null}'
       , '{"instruction-confirmed": true, "people-to-notify-confirmed": true, "repeat-application-confirmed": true, "replacement-attorneys-confirmed": true}'),
       ( 3142812, 'a5b20000000000000000000000000000', NOW(), NOW(), NOW(), NOW(), NOW()
       , true, true, NULL, NULL, 'Mr Test User'
       , '{"type": "property-and-financial", "donor": {"dob": {"date": "1982-11-28T00:00:00.000000+0000"}, "name": {"last": "User", "first": "Another", "title": "Mr"}, "email": {"address": "test_user@digital.justice.gov.uk"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}, "canSign": true, "otherNames": ""}, "preference": "", "instruction": "", "correspondent": {"who": "donor", "name": {"last": "Brock", "first": "Tommy", "title": "Mr"}, "email": {"address": "test_user@digital.justice.gov.uk"}, "phone": null, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}, "company": null, "contactByPost": false, "contactInWelsh": false, "contactDetailsEnteredManually": null}, "peopleToNotify": [{"id": 1, "name": {"last": "Person", "first": "Notifiable", "title": "Mr"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}], "primaryAttorneys": [{"id": 1, "dob": {"date": "1985-01-07T00:00:00.000000+0000"}, "name": {"last": "User", "first": "Celeste", "title": "Miss"}, "type": "human", "email": null, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}], "whoIsRegistering": "donor", "certificateProvider": {"name": {"last": "User", "first": "Francest", "title": "Dr"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}, "replacementAttorneys": [], "primaryAttorneyDecisions": {"how": null, "when": "now", "howDetails": null, "canSustainLife": null}, "replacementAttorneyDecisions": null}'
       , '{"date": null, "email": null, "amount": 82, "method": "cheque", "reference": null, "gatewayReference": null, "reducedFeeLowIncome": null, "reducedFeeAwardedDamages": null, "reducedFeeUniversalCredit": null, "reducedFeeReceivesBenefits": null}'
       , '{"instruction-confirmed": true, "people-to-notify-confirmed": true, "repeat-application-confirmed": true, "replacement-attorneys-confirmed": true}');

-- Scenario 2a
--
-- 1. create a shared space
-- 2. invite two people (people on the call who have notify?)
-- 3. import Make account into shared space (ur-s2-importable@example.com / Pass1234)

INSERT INTO public.users
       ( id, identity, active, created, updated, activated
       , password_hash
       , profile)
VALUES ( 'a2b10000000000000000000000000000', 'ur-s2-importable@example.com', true, NOW(), NOW(), NOW()
       , '$2y$10$C9QCpqBK/9xP7x04nUemhO.OvRc.AWCHOb/N0w8Z2SxOMfSnoNIMO'
       , '{"dob": {"date": "1982-11-28T00:00:00.000000+0000"}, "name": {"last": "Stuart", "first": "John", "title": "Mr"}, "email": {"address": "ur-s2-importable@example.com"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}');

INSERT INTO public.applications
       ( id, "user", "updatedAt", "startedAt", "createdAt", "completedAt", "lockedAt"
       , locked, "whoAreYouAnswered", seed, "repeatCaseNumber", search
       , document
       , payment
       , metadata)
VALUES ( 3142821, 'a2b10000000000000000000000000000', NOW(), NOW(), NOW(), NOW(), NOW()
       , true, true, NULL, NULL, 'Mr Test User'
       , '{"type": "property-and-financial", "donor": {"dob": {"date": "1982-11-28T00:00:00.000000+0000"}, "name": {"last": "User", "first": "Another", "title": "Mr"}, "email": {"address": "test_user@digital.justice.gov.uk"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}, "canSign": true, "otherNames": ""}, "preference": "", "instruction": "", "correspondent": {"who": "donor", "name": {"last": "Brock", "first": "Tommy", "title": "Mr"}, "email": {"address": "test_user@digital.justice.gov.uk"}, "phone": null, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}, "company": null, "contactByPost": false, "contactInWelsh": false, "contactDetailsEnteredManually": null}, "peopleToNotify": [{"id": 1, "name": {"last": "Person", "first": "Notifiable", "title": "Mr"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}], "primaryAttorneys": [{"id": 1, "dob": {"date": "1985-01-07T00:00:00.000000+0000"}, "name": {"last": "User", "first": "Celeste", "title": "Miss"}, "type": "human", "email": null, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}], "whoIsRegistering": "donor", "certificateProvider": {"name": {"last": "User", "first": "Francest", "title": "Dr"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}, "replacementAttorneys": [], "primaryAttorneyDecisions": {"how": null, "when": "now", "howDetails": null, "canSustainLife": null}, "replacementAttorneyDecisions": null}'
       , '{"date": null, "email": null, "amount": 82, "method": "cheque", "reference": null, "gatewayReference": null, "reducedFeeLowIncome": null, "reducedFeeAwardedDamages": null, "reducedFeeUniversalCredit": null, "reducedFeeReceivesBenefits": null}'
       , '{"instruction-confirmed": true, "people-to-notify-confirmed": true, "repeat-application-confirmed": true, "replacement-attorneys-confirmed": true}'),
       ( 3142822, 'a2b10000000000000000000000000000', NOW(), NOW(), NOW(), NOW(), NOW()
       , true, true, NULL, NULL, 'Mr Test User'
       , '{"type": "property-and-financial", "donor": {"dob": {"date": "1982-11-28T00:00:00.000000+0000"}, "name": {"last": "User", "first": "Another", "title": "Mr"}, "email": {"address": "test_user@digital.justice.gov.uk"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}, "canSign": true, "otherNames": ""}, "preference": "", "instruction": "", "correspondent": {"who": "donor", "name": {"last": "Brock", "first": "Tommy", "title": "Mr"}, "email": {"address": "test_user@digital.justice.gov.uk"}, "phone": null, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}, "company": null, "contactByPost": false, "contactInWelsh": false, "contactDetailsEnteredManually": null}, "peopleToNotify": [{"id": 1, "name": {"last": "Person", "first": "Notifiable", "title": "Mr"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}], "primaryAttorneys": [{"id": 1, "dob": {"date": "1985-01-07T00:00:00.000000+0000"}, "name": {"last": "User", "first": "Celeste", "title": "Miss"}, "type": "human", "email": null, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}], "whoIsRegistering": "donor", "certificateProvider": {"name": {"last": "User", "first": "Francest", "title": "Dr"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}, "replacementAttorneys": [], "primaryAttorneyDecisions": {"how": null, "when": "now", "howDetails": null, "canSustainLife": null}, "replacementAttorneyDecisions": null}'
       , '{"date": null, "email": null, "amount": 82, "method": "cheque", "reference": null, "gatewayReference": null, "reducedFeeLowIncome": null, "reducedFeeAwardedDamages": null, "reducedFeeUniversalCredit": null, "reducedFeeReceivesBenefits": null}'
       , '{"instruction-confirmed": true, "people-to-notify-confirmed": true, "repeat-application-confirmed": true, "replacement-attorneys-confirmed": true}');

-- Scenario 2b (ur-s22@example.com in mock One Login)

INSERT INTO public.users
       ( id, identity, active, created, updated, activated
       , profile
	     , one_login_sub, one_login_email)
VALUES ( 'a22b1000000000000000000000000000', 'ur-s22@example.com', true, NOW(), NOW(), NOW()
       , '{"dob": {"date": "1982-11-28T00:00:00.000000+0000"}, "name": {"last": "Grenville", "first": "George", "title": "Mr"}, "email": {"address": "ur-s22@example.com"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}'
	     , 'urn:fdc:mock-one-login:2023:ja+jG/f7f1r5cTguXnhtBhysCuXhqh5HMjz8WzD2tBo=', 'ur-s22@example.com'),
       ( 'a22b2000000000000000000000000000', 'ur-s22-admin@example.com', true, NOW(), NOW(), NOW()
       , '{"dob": {"date": "1982-11-28T00:00:00.000000+0000"}, "name": {"last": "Pitt", "first": "William", "title": "Mr"}, "email": {"address": "ur-s22@example.com"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}'
	     , NULL, NULL),
       ( 'a22b3000000000000000000000000000', 'ur-s22-user@example.com', true, NOW(), NOW(), NOW()
       , '{"dob": {"date": "1982-11-28T00:00:00.000000+0000"}, "name": {"last": "Fitzroy", "first": "Augustus", "title": "Mr"}, "email": {"address": "ur-s22@example.com"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}'
	     , NULL, NULL);

INSERT INTO public.shared_space
       ( id, name, created, updated)
VALUES ( 'c2200000000000000000000000000000', 'My Team', NOW(), NOW() );

INSERT INTO public.shared_space_members
       ( "sharedSpaceId", "userId", created, "isAdmin", "isActive")
VALUES ( 'c2200000000000000000000000000000', 'a22b1000000000000000000000000000', NOW(), TRUE, TRUE),
       ( 'c2200000000000000000000000000000', 'a22b2000000000000000000000000000', NOW(), TRUE, TRUE),
       ( 'c2200000000000000000000000000000', 'a22b3000000000000000000000000000', NOW(), FALSE, TRUE);

-- Scenario 3
--
-- 1. Sign in with ur-s3@example.com
-- 2. Fail to join with My Team / BLAHBLAH
-- 3. Successfully join with My Team / ABCD1234

INSERT INTO public.users
       ( id, identity, active, created, updated, activated
       , profile
	     , one_login_sub, one_login_email)
VALUES ( 'a3b10000000000000000000000000000', 'ur-s3@example.com', true, NOW(), NOW(), NOW()
       , '{"dob": {"date": "1982-11-28T00:00:00.000000+0000"}, "name": {"last": "North", "first": "Frederick", "title": "Mr"}, "email": {"address": "ur-s3@example.com"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}'
	     , 'urn:fdc:mock-one-login:2023:NAaNekhf+kTlHwbF/gVEsiuWnc5rn1QL1r972RAC8lk=', 'ur-s3@example.com'),
       ( 'a3b20000000000000000000000000000', 'ur-s3-admin@example.com', true, NOW(), NOW(), NOW()
       , '{"dob": {"date": "1982-11-28T00:00:00.000000+0000"}, "name": {"last": "Watson-Wentworth", "first": "Charles", "title": "Mr"}, "email": {"address": "ur-s3-admin@example.com"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}'
	     , 'urn:fdc:mock-one-login:2023:2pAIGjJ+3TFfLZaQESCe6dSDEk1EN5ZpwLwMsTaTqzE=', 'ur-s3-admin@example.com');

INSERT INTO public.shared_space
       ( id, name, created, updated)
VALUES ( 'c3000000000000000000000000000000', 'My Team', NOW(), NOW() );

INSERT INTO public.shared_space_members
       ( "sharedSpaceId", "userId", created, "isAdmin", "isActive")
VALUES ( 'c3000000000000000000000000000000', 'a3b20000000000000000000000000000', NOW(), TRUE, TRUE);

INSERT INTO public.shared_space_invites
       ( "sharedSpaceId", "invitedBy", "firstNames", "lastName"
       , email, "isAdmin", code, created, expires)
VALUES ( 'c3000000000000000000000000000000', 'a3b20000000000000000000000000000', 'Test', 'User'
       , 'invited@example.com', TRUE, 'ABCD1234', NOW(), NOW() + INTERVAL '7 days');

-- Scenario 4
--
-- 1. Participant signs in with ur-s4@example.com
-- 2. Researcher signs in with ur-s4-other@example.com
-- 3. Both open the LPA (/lpa/3142841/donor) and the donor sub form
-- 4. Researcher saves a change
-- 5. Participant saves a change

INSERT INTO public.users
       ( id, identity, active, created, updated, activated
       , profile
	     , one_login_sub, one_login_email)
VALUES ( 'a4b10000000000000000000000000000', 'ur-s4@example.com', true, NOW(), NOW(), NOW()
       , '{"dob": {"date": "1982-11-28T00:00:00.000000+0000"}, "name": {"last": "Petty", "first": "William", "title": "Mr"}, "email": {"address": "ur-s4@example.com"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}'
	     , 'urn:fdc:mock-one-login:2023:2NyH4+VAzVfGvqq5I7Fh67tOFTCoSMZ0DI8rHvaUtt4=', 'ur-s4@example.com'),
       ( 'a4b20000000000000000000000000000', 'ur-s4-other@example.com', true, NOW(), NOW(), NOW()
       , '{"dob": {"date": "1982-11-28T00:00:00.000000+0000"}, "name": {"last": "Addington", "first": "Henry", "title": "Mr"}, "email": {"address": "ur-s4-other@example.com"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}'
	     , 'urn:fdc:mock-one-login:2023:r3z29HLsCtor3PXsp8neXQw0VuNlRcQPUWxNkT+YMCg=', 'ur-s4-other@example.com');

INSERT INTO public.shared_space
       ( id, name, created, updated)
VALUES ( 'c4000000000000000000000000000000', 'My Team', NOW(), NOW() );

INSERT INTO public.shared_space_members
       ( "sharedSpaceId", "userId", created, "isAdmin", "isActive")
VALUES ( 'c4000000000000000000000000000000', 'a4b10000000000000000000000000000', NOW(), TRUE, TRUE),
       ( 'c4000000000000000000000000000000', 'a4b20000000000000000000000000000', NOW(), TRUE, TRUE);

INSERT INTO public.applications
       ( id, "user", "updatedAt", "startedAt"
       , locked, "whoAreYouAnswered", seed, "repeatCaseNumber", search
       , "sharedSpaceId", "updatedBy"
       , document
       , metadata)
VALUES ( 3142841, 'a4b10000000000000000000000000000', NOW(), NOW()
       , FALSE, FALSE, NULL, NULL, NULL
       , 'c4000000000000000000000000000000', 'a4b10000000000000000000000000000'
       , '{"type": "property-and-financial", "donor": {"dob": {"date": "1982-11-28T00:00:00.000000+0000"}, "name": {"last": "Petty", "first": "William", "title": "Mr"}, "email": {"address": "ur-s4@example.com"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}, "canSign": true, "otherNames": ""}, "preference": null, "instruction": null, "correspondent": null, "peopleToNotify": [], "primaryAttorneys": [], "whoIsRegistering": null, "certificateProvider": null, "replacementAttorneys": [], "primaryAttorneyDecisions": null, "replacementAttorneyDecisions": null}'
       , '{}');

-- Scenario 6
--
-- 1. Sign in with ur-s6@example.com

INSERT INTO public.users
       ( id, identity, active, created, updated, activated
       , profile
	     , one_login_sub, one_login_email)
VALUES ( 'a6b10000000000000000000000000000', 'ur-s6@example.com', true, NOW(), NOW(), NOW()
       , '{"dob": {"date": "1982-11-28T00:00:00.000000+0000"}, "name": {"last": "Pitt", "first": "William", "title": "Mr"}, "email": {"address": "ur-s6@example.com"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}'
	     , 'urn:fdc:mock-one-login:2023:PrZO5cN/wo5FB+TeiVDlLIp0/HonQMyLezLOmPvavAU=', 'ur-s6@example.com');
