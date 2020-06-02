#!/bin/bash

if test -f /usr/share/gforge/cronjobs/db/cronjobs.ini ; then
  source /usr/share/gforge/cronjobs/db/cronjobs.ini
fi

cd /usr/share/gforge/cronjobs/db/
# Get keywords.
/usr/bin/php /usr/share/gforge/cronjobs/db/getKeywords.php > /usr/share/gforge/cronjobs/db/keywords.csv
# Get ontology.
/usr/bin/php /usr/share/gforge/cronjobs/db/getOntology.php > /usr/share/gforge/cronjobs/db/ontology.csv
# Get group access history.
/usr/bin/php /usr/share/gforge/cronjobs/db/getActivity.php > /usr/share/gforge/cronjobs/db/activity.csv
# Generate recommendation data.
/usr/bin/python /usr/share/gforge/cronjobs/db/genRecommend.py /usr/share/gforge/cronjobs/db/activity.csv > /usr/share/gforge/cronjobs/db/recommend.txt
# Generate recommendation SQL for import into recommended_projects_norms table.
/bin/cat /usr/share/gforge/cronjobs/db/recommend_head.txt /usr/share/gforge/cronjobs/db/recommend.txt > /usr/share/gforge/cronjobs/db/recommend.sql

su - postgres -c "psql -h${db_server} -U${db_user} -d${db_name_ff} -c '\i /usr/share/gforge/cronjobs/db/recommend.sql'"
