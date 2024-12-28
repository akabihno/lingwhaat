#!/bin/sh

gunzip -k /docker-entrypoint-initdb.d/*.sql.gz
exec "$@"
