#!/bin/bash

if test -f /usr/share/gforge/cronjobs/db/cronjobs.ini ; then
  source /usr/share/gforge/cronjobs/db/cronjobs.ini
fi

SUBJECT=`hostname`": OpenSim downloads and forum stats"
DIRNAME=/usr/share/gforge/cronjobs/db

FILENAME=getOpenSimDownloadStats.sql
psql -h${db_server} -U${db_user} -d${db_name_ff} -f ${DIRNAME}/${FILENAME} >| ${DIRNAME}/statsOpenSim.txt

FILENAME=getOpenSimForumStats.sql
psql -h${db_server} -U${db_user} -d${db_name_forum} -f ${DIRNAME}/${FILENAME} >> ${DIRNAME}/statsOpenSim.txt

for x in rcvStats@simtk.org ; do
    echo 'See attached file.' | /usr/bin/mutt -a ${DIRNAME}/statsOpenSim.txt -s "$SUBJECT" -- $x
done
