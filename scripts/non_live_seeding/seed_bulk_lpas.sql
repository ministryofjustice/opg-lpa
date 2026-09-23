-- Creates the user with id passed to seed_bulk_lpas.sh if they don't already exist
INSERT INTO public.users (id, active, created, updated, activated)
VALUES (:user_id, true, now(), now(), now())
ON CONFLICT (id) DO NOTHING;

UPDATE public.users
SET identity = :user_id || '@example.com',
    password_hash = '$2y$10$C9QCpqBK/9xP7x04nUemhO.OvRc.AWCHOb/N0w8Z2SxOMfSnoNIMO',
    profile = jsonb_build_object(
        'name', jsonb_build_object('title', 'Mr', 'first', 'Test', 'last', 'User'),
        'dob', jsonb_build_object('date', '1980-01-01T00:00:00.000000+0000'),
        'email', jsonb_build_object('address', :user_id || '@example.com'),
        'address', jsonb_build_object(
            'address1', '1 Test Street',
            'address2', 'Test Town',
            'postcode', 'AB1 2CD'
        )
    )
WHERE id = :user_id;

INSERT INTO public.applications (
    id, "user", "updatedAt", "startedAt", "createdAt", "completedAt", "lockedAt",
    locked, "whoAreYouAnswered", document, payment, metadata, search
)
SELECT
    (SELECT COALESCE(MAX(id), 0) FROM public.applications) + g,
    :user_id,
    now(), now(), now(), NULL, NULL,
    false, true,
    jsonb_build_object(
        'type', 'property-and-financial',
        'donor', jsonb_build_object(
            'name', jsonb_build_object('title', 'Mr', 'first', 'Test', 'last', 'Donor' || g),
            'dob', jsonb_build_object('date', '1980-01-01T00:00:00.000000+0000'),
            'address', jsonb_build_object('address1', '1 Test Street', 'postcode', 'AB1 2CD')
        )
    ),
    jsonb_build_object('amount', 82, 'method', 'cheque'),
    '{}'::jsonb,
    'Mr Test Donor' || g
FROM generate_series(1, :count) AS g;
