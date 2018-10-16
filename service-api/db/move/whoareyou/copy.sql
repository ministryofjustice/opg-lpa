TRUNCATE TABLE who_are_you;
\COPY who_are_you (who,qualifier,logged) FROM 'whoareyou-converted.csv' DELIMITER ',' CSV
