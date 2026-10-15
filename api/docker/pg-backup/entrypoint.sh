#!/usr/bin/env sh
set -e

printenv > /etc/environment
cron -f
