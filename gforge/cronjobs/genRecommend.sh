#!/bin/bash
cd /usr/share/gforge/cronjobs/
# Get keywords.
/usr/bin/php /usr/share/gforge/cronjobs/getKeywords.php > /usr/share/gforge/cronjobs/keywords.csv
# Get ontology.
/usr/bin/php /usr/share/gforge/cronjobs/getOntology.php > /usr/share/gforge/cronjobs/ontology.csv
# Get group access history.
/usr/bin/php /usr/share/gforge/cronjobs/getActivity.php > /usr/share/gforge/cronjobs/activity.csv
# Generate recommendation data.
/usr/bin/python /usr/share/gforge/cronjobs/genRecommend.py /usr/share/gforge/cronjobs/activity.csv > /usr/share/gforge/cronjobs/recommend.txt
# Generate recommendation SQL for import into recommended_projects_norms table.
/bin/cat /usr/share/gforge/cronjobs/recommend_head.txt /usr/share/gforge/cronjobs/recommend.txt > /usr/share/gforge/cronjobs/recommend.sql
