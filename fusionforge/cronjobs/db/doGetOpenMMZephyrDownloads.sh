#!/bin/bash

if test -f /usr/share/gforge/cronjobs/db/cronjobs.ini ; then
  source /usr/share/gforge/cronjobs/db/cronjobs.ini
fi

SUBJECT=`hostname`": OpenMM Zephyr downloads stats"
DIRNAME=/usr/share/gforge/cronjobs/db

FILENAME=getOpenMMZephyrDownloadStats1.sql
psql -h${db_server} -U${db_user} -d${db_name_ff} -f ${DIRNAME}/${FILENAME} >| ${DIRNAME}/statsOpenMMZephyrStats.txt

FILENAME=getOpenMMZephyrDownloadStats2.sql
psql -h${db_server} -U${db_user} -d${db_name_ff} -f ${DIRNAME}/${FILENAME} >> ${DIRNAME}/statsOpenMMZephyrStats.txt

for x in webmaster@simtk.org ; do
    echo 'See attached file.' | /usr/bin/mutt -a ${DIRNAME}/statsOpenMMZephyrStats.txt -s "$SUBJECT" -- $x
done
