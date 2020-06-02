#!/bin/bash

BASE_FILENAME=/usr/share/gforge/cronjobs/db/getNewUsers
SUBJECT=`hostname`": New simtk.org users"

${BASE_FILENAME}.sh >| ${BASE_FILENAME}.txt

for x in webmaster@simtk.org ; do
    echo 'See attached file.' | /usr/bin/mutt -a ${BASE_FILENAME}.txt -s "$SUBJECT" -- $x
done

