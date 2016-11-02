#!/bin/bash

BASE_FILENAME=/usr/share/gforge/cronjobs/getNewUsers
SUBJECT="New simtk.org users"

${BASE_FILENAME}.sh >| ${BASE_FILENAME}.txt

for x in webmaster@simtk.org ; do
    echo 'See attached file.' | mutt -a ${BASE_FILENAME}.txt -s "$SUBJECT" -- $x
done

