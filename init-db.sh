#!/bin/sh

gunzip /docker-entrypoint-initdb.d/*.sql.gz
exec "$@"
