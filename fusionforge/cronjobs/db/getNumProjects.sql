--

SELECT current_date
      ,COUNT(register_time) AS num_projects
      ,COUNT(download_state) AS public_downloads
      ,COUNT(p_scm_state) AS public_svn_repository
      ,COUNT(a_scm_state) AS active_svn_repository
      ,COUNT(download_scm_state) AS pubic_download_or_svn_repository
      ,COUNT(datashare_state) AS datashare_study
      ,COUNT(public_datashare_state) AS public_datashare_study

FROM (
SELECT p_all.group_name AS group_name
      ,p_all.register_time AS register_time
      ,p_pub.state AS download_state
      ,p_scm.state AS p_scm_state
      ,a_scm.state AS a_scm_state
      ,p_pub_or_scm.state AS download_scm_state
      ,p_ds.state AS datashare_state
      ,p_ds_pub.state AS public_datashare_state
FROM (
SELECT g.unix_group_name AS group_name
      ,g.register_time AS register_time
  FROM groups AS g
  WHERE g.status='A'
    AND register_time>0
  ORDER BY g.register_time
) AS p_all

LEFT OUTER JOIN
(SELECT g.unix_group_name AS group_name
       ,1 AS state
  FROM
(SELECT g.group_id,
    g.status AS g_status,
    g.simtk_is_public AS g_is_public,
    p.package_id,
    p.status_id AS p_status_id,
    p.is_public AS p_is_public,
    r.release_id,
    r.status_id AS r_status_id,
    f.file_id,
    f.type_id AS f_type_id,
        CASE
            WHEN g.status = 'A'::bpchar AND g.simtk_is_public = 1 AND g.simtk_is_system = 0 AND p.status_id = 1 AND p.is_public = 1 AND r.status_id = 1 AND f.type_id <> 2000 THEN 1
            ELSE 0
        END AS is_public_download_file
   FROM groups g,
    frs_package p,
    frs_release r,
    frs_file f
  WHERE g.group_id = p.group_id AND p.package_id = r.package_id AND r.release_id = f.release_id)
AS dfiv
  JOIN frs_file AS ff
    ON ff.file_id=dfiv.file_id
  JOIN groups AS g
    ON g.group_id=dfiv.group_id
  WHERE dfiv.g_status='A'
    AND dfiv.g_is_public=1
    AND dfiv.p_status_id=1
    AND p_is_public=1
    AND r_status_id=1
    AND is_public_download_file=1
    AND ff.release_time>0
  GROUP BY g.unix_group_name
) AS p_pub
ON p_pub.group_name=p_all.group_name

LEFT OUTER JOIN
(SELECT g.unix_group_name AS group_name
       ,1 AS state
  FROM 
(SELECT g.group_id,
    g.status AS g_status,
    g.simtk_is_public AS g_is_public,
    p.package_id,
    p.status_id AS p_status_id,
    p.is_public AS p_is_public,
    r.release_id,
    r.status_id AS r_status_id,
    f.file_id,
    f.type_id AS f_type_id,
        CASE
            WHEN g.status = 'A'::bpchar AND g.simtk_is_public = 1 AND g.simtk_is_system = 0 AND p.status_id = 1 AND p.is_public = 1 AND r.status_id = 1 AND f.type_id <> 2000 THEN 1
            ELSE 0
        END AS is_public_download_file
   FROM groups g,
    frs_package p,
    frs_release r,
    frs_file f
  WHERE g.group_id = p.group_id AND p.package_id = r.package_id AND r.release_id = f.release_id)
AS dfiv
  JOIN frs_file AS ff
    ON ff.file_id=dfiv.file_id
  JOIN groups AS g
    ON g.group_id=dfiv.group_id
  JOIN stats_cvs_group AS scg
    ON scg.group_id=g.group_id
  WHERE dfiv.g_status='A'
    AND dfiv.g_is_public=1
    AND g.use_scm=1
    AND g.simtk_enable_anonscm=1
    AND scg.commits>0
  GROUP BY g.unix_group_name
) AS p_scm
ON p_scm.group_name=p_all.group_name

LEFT OUTER JOIN
(SELECT g.unix_group_name AS group_name
       ,1 AS state
  FROM 
(SELECT g.group_id,
    g.status AS g_status,
    g.simtk_is_public AS g_is_public,
    p.package_id,
    p.status_id AS p_status_id,
    p.is_public AS p_is_public,
    r.release_id,
    r.status_id AS r_status_id,
    f.file_id,
    f.type_id AS f_type_id,
        CASE
            WHEN g.status = 'A'::bpchar AND g.simtk_is_public = 1 AND g.simtk_is_system = 0 AND p.status_id = 1 AND p.is_public = 1 AND r.status_id = 1 AND f.type_id <> 2000 THEN 1
            ELSE 0
        END AS is_public_download_file
   FROM groups g,
    frs_package p,
    frs_release r,
    frs_file f
  WHERE g.group_id = p.group_id AND p.package_id = r.package_id AND r.release_id = f.release_id)
AS dfiv
  JOIN frs_file AS ff
    ON ff.file_id=dfiv.file_id
  JOIN groups AS g
    ON g.group_id=dfiv.group_id
  JOIN stats_cvs_group AS scg
    ON scg.group_id=g.group_id
  WHERE dfiv.g_status='A'
    AND g.use_scm=1
    AND scg.commits>0
  GROUP BY g.unix_group_name
) AS a_scm
ON a_scm.group_name=p_all.group_name

LEFT OUTER JOIN
(SELECT g.unix_group_name AS group_name
       ,1 AS state
  FROM 
(SELECT g.group_id,
    g.status AS g_status,
    g.simtk_is_public AS g_is_public,
    p.package_id,
    p.status_id AS p_status_id,
    p.is_public AS p_is_public,
    r.release_id,
    r.status_id AS r_status_id,
    f.file_id,
    f.type_id AS f_type_id,
        CASE
            WHEN g.status = 'A'::bpchar AND g.simtk_is_public = 1 AND g.simtk_is_system = 0 AND p.status_id = 1 AND p.is_public = 1 AND r.status_id = 1 AND f.type_id <> 2000 THEN 1
            ELSE 0
        END AS is_public_download_file
   FROM groups g,
    frs_package p,
    frs_release r,
    frs_file f
  WHERE g.group_id = p.group_id AND p.package_id = r.package_id AND r.release_id = f.release_id)
AS dfiv
  JOIN frs_file AS ff
    ON ff.file_id=dfiv.file_id
  JOIN groups AS g
    ON g.group_id=dfiv.group_id
  LEFT JOIN stats_cvs_group AS scg
    ON scg.group_id=g.group_id
  WHERE dfiv.g_status='A'
    AND dfiv.g_is_public=1
    AND (
      (dfiv.p_status_id=1
         AND p_is_public=1
         AND r_status_id=1
         AND is_public_download_file=1
         AND ff.release_time>0)
      OR (g.use_scm=1 AND simtk_enable_anonscm=1 AND scg.commits>0)
    )
  GROUP BY g.unix_group_name
) AS p_pub_or_scm
ON p_pub_or_scm.group_name=p_all.group_name

LEFT OUTER JOIN
(SELECT g.unix_group_name AS group_name
       ,1 AS state
  FROM
(SELECT g.group_id,
    g.status AS g_status,
    g.simtk_is_public AS g_is_public,
    d.active AS d_active
   FROM groups g,
    plugin_datashare d
  WHERE g.group_id = d.group_id)
AS ds
  JOIN groups AS g
    ON g.group_id=ds.group_id
  WHERE ds.g_status='A'
    AND ds.g_is_public=1
    AND ds.d_active=1
  GROUP BY g.unix_group_name
) AS p_ds
ON p_ds.group_name=p_all.group_name

LEFT OUTER JOIN
(SELECT g.unix_group_name AS group_name
       ,1 AS state
  FROM
(SELECT g.group_id,
    g.status AS g_status,
    g.simtk_is_public AS g_is_public,
    d.active AS d_active,
    d.is_private AS d_is_private
   FROM groups g,
    plugin_datashare d
  WHERE g.group_id = d.group_id)
AS ds
  JOIN groups AS g
    ON g.group_id=ds.group_id
  WHERE ds.g_status='A'
    AND ds.g_is_public=1
    AND ds.d_active=1
    AND ds.d_is_private=0
  GROUP BY g.unix_group_name
) AS p_ds_pub
ON p_ds_pub.group_name=p_all.group_name

) AS t

