#!/bin/bash

echo $(date):

SQL="SELECT COUNT(*) FROM users WHERE TO_TIMESTAMP(add_date) < CURRENT_TIMESTAMP AND status='P' AND email like '%otmail%';"
CMD="psql -h${DB_SERVER} -U${DB_USER} -d${DB_NAME} -t -c \"$SQL\" "
echo Pending Hotmail Users: $(eval $CMD)

SQL="SELECT COUNT(*) FROM users WHERE TO_TIMESTAMP(add_date) < CURRENT_TIMESTAMP AND status='P';"
CMD="psql -h${DB_SERVER} -U${DB_USER} -d${DB_NAME} -t -c \"$SQL\" "
echo All Pending Users: $(eval $CMD)

SQL="SELECT COUNT(*) FROM users WHERE TO_TIMESTAMP(add_date) < CURRENT_TIMESTAMP AND status='A';"
CMD="psql -h${DB_SERVER} -U${DB_USER} -d${DB_NAME} -t -c \"$SQL\" "
echo All Active Users: $(eval $CMD)

echo


