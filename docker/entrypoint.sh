#!/bin/sh
set -e

php bin/console doctrine:migrations:migrate --no-interaction
exec frankenphp run --config /app/Caddyfile
