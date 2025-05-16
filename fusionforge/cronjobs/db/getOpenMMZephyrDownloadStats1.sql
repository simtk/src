--

SELECT count(*) cnt, user_id, ip_address, filename, simtk_filetype FROM frs_dlstats_file d JOIN frs_file f ON f.file_id=d.file_id JOIN frs_frpg_view a ON a.file_id=d.file_id WHERE group_id=352 GROUP BY user_id, ip_address, filename, simtk_filetype ORDER BY cnt DESC LIMIT 15;
