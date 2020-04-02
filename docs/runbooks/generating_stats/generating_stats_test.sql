SELECT
	COUNT(1) ,
	to_char(completedAt,'Mon') as Mon,
	extract(year from completedAt) as Yr,
	payment->>'method'
FROM applications
WHERE
	completedAt IS NOT NULL AND
	completedAt BETWEEN
		'2019-01-01 00:00:00' AND
		'2020-03-31 23:59:59'
GROUP BY
	1,
	2,
	payment->>'method';