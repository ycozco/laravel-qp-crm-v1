#!/bin/sh
set -eu

export CONTAINER_ROLE=queue
/usr/local/bin/bootstrap-crm

exec php artisan queue:work --tries=3 --timeout=90 --sleep=3
