#!/bin/bash

if test -f /usr/share/gforge/cronjobs/db/cronjobs.ini ; then
  source /usr/share/gforge/cronjobs/db/cronjobs.ini
fi

CURRENT_M=$(date +'%m')
CURRENT_Y=$(date +'%Y')
CURRENT_Month=$(date +'%b')

BASE_FILENAME=/usr/share/gforge/cronjobs/db/getProjectDownloadStatsWithMonth
SUBJECT=`hostname`": Download stats (before ${CURRENT_Month} 01 ${CURRENT_Y})"

cp /usr/share/gforge/cronjobs/db/getProjectDownloadStatsWithMonthTemplate.sql /usr/share/gforge/cronjobs/db/getProjectDownloadStatsWithMonth.sql
sed -i -e "s/the_month/${CURRENT_Y}${CURRENT_M}/g" /usr/share/gforge/cronjobs/db/getProjectDownloadStatsWithMonth.sql ;

psql -h${db_server} -U${db_user} -d${db_name_ff} -f ${BASE_FILENAME}.sql >| ${BASE_FILENAME}.txt

for x in webmaster@simtk.org ; do
    echo 'See attached file.' | /usr/bin/mutt -a ${BASE_FILENAME}.txt -s "$SUBJECT" -- $x
done

