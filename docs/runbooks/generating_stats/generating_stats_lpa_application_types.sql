-- Query for LPA application types
-- edit this date range as needed
\set datefrom '2019-11-23 00:00:00'
\set dateto '2020-11-22 23:59:59'

SELECT
    applications."document"->>'type' AS "Type",
	TO_CHAR(applications."completedAt", 'MM') AS "Mon",
	TO_CHAR(applications."completedAt", 'YYYY') AS "Yr",
    COUNT(1)
FROM applications
WHERE
	applications."completedAt" BETWEEN :'datefrom' and :'dateto'
GROUP BY
	1,
	3,
	2
ORDER BY
    1 ASC,
	3 ASC,
	2 ASC
;
