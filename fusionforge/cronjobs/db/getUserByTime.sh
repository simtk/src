#!/bin/bash

if test -f /usr/share/gforge/cronjobs/db/cronjobs.ini ; then
	source /usr/share/gforge/cronjobs/db/cronjobs.ini
fi

CURRENT_M=$(date +'%b')
CURRENT_Y=$(date +'%Y')

SQL_ACTIVE="SELECT COUNT(*) FROM users WHERE TO_TIMESTAMP(add_date) < TO_TIMESTAMP('01-${CURRENT_M}-${CURRENT_Y}', 'dd-Mon-YYYY') AND status='A';"
SQL_SUSPENDED="SELECT COUNT(*) FROM users WHERE TO_TIMESTAMP(add_date) < TO_TIMESTAMP('01-${CURRENT_M}-${CURRENT_Y}', 'dd-Mon-YYYY') AND (status='S' OR status='R');"
CMD_ACTIVE="psql -h${db_server} -U${db_user} -d${db_name_ff} -t -c \"$SQL_ACTIVE\" "
CMD_SUSPENDED="psql -h${db_server} -U${db_user} -d${db_name_ff} -t -c \"$SQL_SUSPENDED\" "
SQL_RECORD="INSERT INTO user_count_hist (month, active, suspended_edited) VALUES ('${CURRENT_M} 01 ${CURRENT_Y}', $(eval ${CMD_ACTIVE}), $(eval ${CMD_SUSPENDED}));"
CMD_RECORD="psql -h${db_server} -U${db_user} -d${db_name_ff} -t -c \"$SQL_RECORD\" "
echo "$CURRENT_M $CURRENT_Y Active: $(eval $CMD_ACTIVE) Suspended/Edited: $(eval $CMD_SUSPENDED)" > /dev/null
echo $(eval $CMD_RECORD) > /dev/null
echo "Date        | Active| Suspended/Edited"
SQL_PREV="SELECT month, active, suspended_edited FROM user_count_hist;"
CMD_PREV="psql -h${db_server} -U${db_user} -d${db_name_ff} -A -F ' | ' -R '^' -t -c \"$SQL_PREV\" "
echo $(eval $CMD_PREV) | tr '^' '\n'
