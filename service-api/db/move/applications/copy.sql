BEGIN;
TRUNCATE TABLE applications;
\COPY applications FROM 'applications-converted.csv' DELIMITER ',' CSV QUOTE '''' FREEZE;
COMMIT;

VACUUM ANALYZE;
