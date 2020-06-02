#!/bin/bash

if test -f /usr/share/gforge/cronjobs/db/cronjobs.ini ; then
  source /usr/share/gforge/cronjobs/db/cronjobs.ini
fi

TIME_RANGE=$((7*24*3600))
SQL="SELECT TO_CHAR(TO_TIMESTAMP(add_date),'dd-Mon-YYYY'),
            user_name,
            realname,
            university_name,
            email,
            found_us,
            found_us_note
         FROM users
         WHERE status='A'
            AND add_date>=EXTRACT(EPOCH FROM CURRENT_TIMESTAMP)-$TIME_RANGE
         ORDER BY add_date;"
CMD="psql -h${db_server} -U${db_user} -d${db_name_ff} -t -c \"$SQL\" "
eval $CMD | head -n -1 

