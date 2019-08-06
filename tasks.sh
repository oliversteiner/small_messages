#!/usr/bin/env bash

#
# Copy this file to your home directory, rename it and and make it executable
#     chmod +x YOURWEBSITE_tasks.sh
#
# add it to your crontab
#
#
#


SITE="https://example.local"


#  If you testing on a local development server like MAMP
#  add --insecure for bypassing HTTPS errors
#
# curl --insecure ${SITE}/smmg/task/cron_test

curl ${SITE}/smmg/task/cron_test

