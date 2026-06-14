#!/bin/sh
set -eu

export CONTAINER_ROLE=web
/usr/local/bin/bootstrap-crm

exec apache2-foreground
