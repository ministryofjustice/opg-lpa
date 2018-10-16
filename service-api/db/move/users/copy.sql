TRUNCATE TABLE users;
\COPY users FROM 'users-converted.csv' DELIMITER ',' CSV

VACUUM ANALYZE;
