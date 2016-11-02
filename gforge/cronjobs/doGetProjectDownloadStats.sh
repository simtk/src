#!/bin/bash

BASE_FILENAME=/usr/share/gforge/cronjobs/getProjectDownloadStats
SUBJECT="Download stats"

psql -h${DB_SERVER} -U${DB_USER} -d${DB_NAME} -f ${BASE_FILENAME}.sql >| ${BASE_FILENAME}.txt

for x in webmaster@simtk.org ; do
    echo 'See attached file.' | mutt -a ${BASE_FILENAME}.txt -s "$SUBJECT" -- $x
done

