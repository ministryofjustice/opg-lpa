-- get totals of created, completed and started applications
-- edit this date range as needed
\set datefrom '2019-11-23 00:00:00'
\set dateto '2020-11-22 23:59:59'

SELECT
    'completed' as statistic,COUNT(1) as total
FROM applications
WHERE
	applications."completedAt" BETWEEN :'datefrom' AND :'dateto'

UNION
SELECT
    'created' as statistic,COUNT(1) as total
FROM applications
WHERE
	applications."createdAt" BETWEEN :'datefrom' and :'dateto'

UNION
SELECT
    'started' as statistic,COUNT(1) as total
FROM applications
WHERE
	applications."startedAt" BETWEEN :'datefrom' and :'dateto'
ORDER by 1
;
