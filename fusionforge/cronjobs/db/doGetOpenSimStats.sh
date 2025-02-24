#!/bin/bash

if test -f /usr/share/gforge/cronjobs/db/cronjobs.ini ; then
  source /usr/share/gforge/cronjobs/db/cronjobs.ini
fi

SUBJECT=`hostname`": OpenSim downloads and forum stats"

FILENAME=/usr/share/gforge/cronjobs/db/getOpenSimDownloadStats.sql
psql -h${db_server} -U${db_user} -d${db_name_ff} -f ${FILENAME} >| statsOpenSim.txt

FILENAME=/usr/share/gforge/cronjobs/db/getOpenSimForumStats.sql
psql -h${db_server} -U${db_user} -d${db_name_forum} -f ${FILENAME} >> statsOpenSim.txt

#for x in rcvStats@simtk.org ; do
#    echo 'See attached file.' | /usr/bin/mutt -a ${BASE_FILENAME}.txt -s "$SUBJECT" -- $x
#done
