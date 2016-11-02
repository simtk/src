#!/bin/bash

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
CMD="psql -h${DB_SERVER} -U${DB_USER} -d${DB_NAME} -t -c \"$SQL\" "
eval $CMD | head -n -1 

