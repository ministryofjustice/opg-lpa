TRUNCATE TABLE deletion_log;
\COPY deletion_log ("identity_hash","type","reason","loggedAt") FROM 'log-dump.csv' DELIMITER ',' CSV HEADER
