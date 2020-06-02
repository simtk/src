--


SELECT *

FROM (
SELECT g.unix_group_name AS unix_group_name
      ,g.group_id
      ,COALESCE(MAX(d_total.num_downloads),0) AS total_file_downloads
      ,COUNT(d_unique.*) AS unique_downloaders
FROM groups as g
LEFT OUTER JOIN (
SELECT fp.group_id AS group_id
      ,COUNT(*) AS num_downloads
  FROM frs_dlstats_file AS  fdf
  JOIN frs_file AS  ff
    ON ff.file_id=fdf.file_id
  JOIN frs_release AS  fr
    ON fr.release_id=ff.release_id
  JOIN frs_package AS  fp
    ON fp.package_id=fr.package_id
  WHERE month < the_month
  GROUP BY fp.group_id
) AS d_total
ON d_total.group_id=g.group_id
AND g.status='A'
LEFT OUTER JOIN (
SELECT fp.group_id AS group_id
      ,u.user_name AS user_name
      ,COUNT(*) AS num_downloads
  FROM frs_dlstats_file AS  fdf
  JOIN frs_file AS  ff
    ON ff.file_id=fdf.file_id
  JOIN frs_release AS  fr
    ON fr.release_id=ff.release_id
  JOIN frs_package AS  fp
    ON fp.package_id=fr.package_id
  JOIN users AS  u
    ON u.user_id=fdf.user_id
  WHERE month < the_month
  GROUP BY fp.group_id
          ,u.user_name
) AS d_unique
ON d_unique.group_id=g.group_id
GROUP BY g.unix_group_name, g.group_id
ORDER BY g.unix_group_name
) AS t
WHERE t.unix_group_name='cv-gmodels'
   OR t.unix_group_name='femur-model'
   OR t.unix_group_name='foldvillin'
   OR t.unix_group_name='low-ext-model'
   OR t.unix_group_name='msmbuilder'
   OR t.unix_group_name='nast'
   OR t.unix_group_name='neck_mechanics'
   OR t.unix_group_name='nmblmodels'
   OR t.unix_group_name='openmm'
   OR t.unix_group_name='opensim'
   OR t.unix_group_name='rnatoolbox'
   OR t.unix_group_name='simtkcore'
   OR t.unix_group_name='simvascular'
   OR t.unix_group_name='torso_legs'
   OR t.unix_group_name='up-ext-model'
   OR t.unix_group_name='wrist-model'
   OR t.unix_group_name='zephyr'

ORDER BY t.unix_group_name
;
