#!/bin/bash

CURRENT_M=$(date +'%b')
CURRENT_Y=$(date +'%Y')

PREV_M=Dec
PREV_Y=2004

for y in 2005 2006 2007 2008 2009 2010 2011 2012 2013 2014 2015 2016 2017 2018 2019 2020 2021 2022 2023; do
   for m in Jan Feb Mar Apr May Jun Jul Aug Sep Oct Nov Dec; do
      SQL="SELECT COUNT(*) FROM users
                  WHERE TO_TIMESTAMP(add_date) < TO_TIMESTAMP('01-${m}-${y}', 'dd-Mon-YYYY')
                      AND status='A';"
      CMD="psql -h${DB_SERVER} -U${DB_USER} -d${DB_NAME} -t -c \"$SQL\" "
      echo -n $PREV_M $PREV_Y $(eval $CMD)
      if [[ $CURRENT_Y == $PREV_Y && $CURRENT_M == $PREV_M ]]; then
          amDone='Yup'
          echo "  (as of $(date +'%l:%M %p, %A, %B %d, %Y'))"
          break
      fi 
      echo
      PREV_M=$m
      PREV_Y=$y
   done
   if [[ -n $amDone ]]; then
       break
   fi 
done


