\COPY (SELECT id FROM users WHERE identity IS NOT NULL) TO 'users.csv' CSV DELIMITER ','
