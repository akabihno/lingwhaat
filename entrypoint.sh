#!/bin/sh

until mysql -h"$DB_HOST" -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" -e 'select 1' "$MYSQL_DATABASE"; do
  >&2 echo "MySQL is unavailable - sleeping"
  sleep 1
done

>&2 echo "MySQL is up - executing command"

# Run migrations
php artisan migrate

# Execute the main process
exec "$@"
