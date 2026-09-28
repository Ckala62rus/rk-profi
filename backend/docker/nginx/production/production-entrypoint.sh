#!/bin/sh
set -eu

: "${NGINX_MODE:?NGINX_MODE must be http or https}"
: "${SERVER_NAME:?SERVER_NAME must be set}"
: "${CANONICAL_HOST:?CANONICAL_HOST must be set}"

case "$NGINX_MODE" in
    http|https)
        ;;
    *)
        echo "NGINX_MODE must be http or https" >&2
        exit 1
        ;;
esac

envsubst '$SERVER_NAME $CANONICAL_HOST $NGINX_UPSTREAM $LETSENCRYPT_CERT_NAME' \
    < "/etc/nginx/production-templates/${NGINX_MODE}.conf.template" \
    > /etc/nginx/conf.d/default.conf

rm -rf /var/www/public/storage
ln -s /var/www/storage/app/public /var/www/public/storage

exec /docker-entrypoint.sh "$@"
