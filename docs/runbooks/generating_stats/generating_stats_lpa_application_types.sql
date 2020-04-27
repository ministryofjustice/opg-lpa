-- Query for LPA application types
SELECT
    applications."document"->>'type' AS "Type",
	TO_CHAR(applications."completedAt", 'MM') AS "Mon",
	TO_CHAR(applications."completedAt", 'YYYY') AS "Yr",
    COUNT(1)
FROM applications
WHERE
	applications."completedAt" BETWEEN '2019-01-01 00:00:00' AND '2020-03-31 23:59:59'
GROUP BY
	1,
	3,
	2
ORDER BY
    1 ASC,
	3 ASC,
	2 ASC
;