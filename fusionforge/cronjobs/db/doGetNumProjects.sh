#!/bin/bash

if test -f /usr/share/gforge/cronjobs/db/cronjobs.ini ; then
  source /usr/share/gforge/cronjobs/db/cronjobs.ini
fi

BASE_FILENAME=/usr/share/gforge/cronjobs/db/getNumProjects
SUBJECT=`hostname`": All projects and projects with public downloads or source repository"

psql -h${db_server} -U${db_user} -d${db_name_ff} -f ${BASE_FILENAME}.sql >| ${BASE_FILENAME}.txt

for x in rcvStats@simtk.org ; do
    echo 'See attached file.' | /usr/bin/mutt -a ${BASE_FILENAME}.txt -s "$SUBJECT" -- $x
done

