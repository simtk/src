#!/bin/bash

BASE_FILENAME=/usr/share/gforge/cronjobs/db/getUserByTime
SUBJECT=`hostname`": Number of new simtk.org user last month"

${BASE_FILENAME}.sh >| ${BASE_FILENAME}.txt

for x in webmaster@simtk.org ; do
    echo 'See attached file.' | /usr/bin/mutt -a ${BASE_FILENAME}.txt -s "$SUBJECT" -- $x
done

