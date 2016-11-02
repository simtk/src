#!/bin/bash

BASE_FILENAME=/usr/share/gforge/cronjobs/getDailyUsersInfo
SUBJECT="Number of pending/active users"

${BASE_FILENAME}.sh >> ${BASE_FILENAME}.txt

for x in webmaster@simtk.org ; do
    echo 'See attached file.' | mutt -a ${BASE_FILENAME}.txt -s "$SUBJECT" -- $x
done

