BEGIN;
TRUNCATE TABLE applications;
\COPY applications FROM 'applications-converted.csv' DELIMITER ',' CSV QUOTE '''' FREEZE;
COMMIT;

VACUUM ANALYZE;

UPDATE applications SET
    "user" = NULL,
    "updatedAt" = NOW(),
    "startedAt" = NULL,
    "createdAt" = NULL,
    "completedAt" = NULL,
    "lockedAt" = NULL,
    locked = NULL,
    "whoAreYouAnswered" = NULL,
    seed = NULL,
    "repeatCaseNumber" = NULL,
    document = NULL,
    payment = NULL,
    metadata = NULL,
    search = NULL
WHERE "user" IS NOT NULL AND "user" NOT IN(
	SELECT id FROM users WHERE identity IS NOT NULL
);
