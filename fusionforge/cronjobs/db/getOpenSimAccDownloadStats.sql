--

SELECT CAST(TO_CHAR(date_trunc('month', CURRENT_DATE) - '1 month'::interval, 'yyyymm') AS INTEGER) AS month, COUNT(*) AS acc_total_file_downloads, COUNT(DISTINCT user_id) AS unique_downloaders FROM frs_file ff JOIN frs_release fr ON ff.release_id=fr.release_id JOIN frs_package fp ON fr.package_id=fp.package_id JOIN frs_dlstats_file df ON ff.file_id=df.file_id WHERE group_id=91 AND month<=CAST(TO_CHAR(date_trunc('month', CURRENT_DATE) - '1 month'::interval, 'yyyymm') AS INTEGER);
