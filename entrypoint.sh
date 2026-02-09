#!/bin/sh
set -e

: "Waiting for DB at ${DB_HOST:-db}:${DB_PORT:-5432}..."
HOST=${DB_HOST:-db}
PORT=${DB_PORT:-5432}

while ! nc -z $HOST $PORT; do
  echo "Waiting for postgres at $HOST:$PORT..."
  sleep 1
done

python manage.py migrate --noinput
python manage.py collectstatic --noinput

if [ "$1" = "runserver" ]; then
  shift
  exec python manage.py runserver "$@"
fi

exec "$@"
