#!/bin/sh
set -eu

export CONTAINER_ROLE=scheduler
/usr/local/bin/bootstrap-crm

exec php artisan schedule:work
