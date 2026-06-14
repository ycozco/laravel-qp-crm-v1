#!/bin/sh
set -eu

APP_DIR="${APP_DIR:-/var/www/app}"
ROLE="${CONTAINER_ROLE:-web}"
KEY_FILE="${APP_DIR}/storage/framework/app-key.env"
BOOTSTRAP_MARKER="${APP_DIR}/storage/framework/crm-bootstrap.done"

mkdir -p "${APP_DIR}/storage/framework" "${APP_DIR}/storage/logs" "${APP_DIR}/bootstrap/cache"
chown -R www-data:www-data "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

if [ -f "${KEY_FILE}" ]; then
    # shellcheck disable=SC1090
    . "${KEY_FILE}"
    export APP_KEY
fi

generate_app_key() {
    php -r 'echo "base64:".base64_encode(random_bytes(32));'
}

wait_for_db() {
    echo "Esperando MySQL en ${DB_HOST:-mysql}:${DB_PORT:-3306}..."
    until php -r '
        $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s", getenv("DB_HOST"), getenv("DB_PORT") ?: "3306", getenv("DB_DATABASE"));
        new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD"), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    '; do
        sleep 3
    done
}

wait_for_redis() {
    if [ -z "${REDIS_HOST:-}" ]; then
        return 0
    fi

    echo "Esperando Redis en ${REDIS_HOST}:${REDIS_PORT:-6379}..."
    until php -r '
        $redis = new Redis();
        $redis->connect(getenv("REDIS_HOST"), (int) (getenv("REDIS_PORT") ?: 6379), 2.0);
        $password = getenv("REDIS_PASSWORD");
        if ($password && strtolower($password) !== "null") {
            $redis->auth($password);
        }
        exit($redis->ping() ? 0 : 1);
    '; do
        sleep 3
    done
}

ensure_runtime_key() {
    if [ -n "${APP_KEY:-}" ]; then
        return 0
    fi

    if [ "${ROLE}" = "web" ]; then
        APP_KEY="$(generate_app_key)"
        export APP_KEY
        printf 'APP_KEY=%s\n' "${APP_KEY}" > "${KEY_FILE}"
        chmod 600 "${KEY_FILE}"
        return 0
    fi

    echo "Esperando APP_KEY generado por el contenedor web..."
    while [ ! -f "${KEY_FILE}" ]; do
        sleep 2
    done

    # shellcheck disable=SC1090
    . "${KEY_FILE}"
    export APP_KEY
}

prime_caches() {
    php artisan optimize:clear >/dev/null
    php artisan config:cache >/dev/null
    php artisan route:cache >/dev/null
    php artisan view:cache >/dev/null
}

bootstrap_web() {
    wait_for_db
    wait_for_redis

    if [ ! -f "${BOOTSTRAP_MARKER}" ]; then
        : "${CRM_OWNER_NAME:?CRM_OWNER_NAME es obligatorio}"
        : "${CRM_OWNER_EMAIL:?CRM_OWNER_EMAIL es obligatorio}"
        : "${CRM_OWNER_PASSWORD:?CRM_OWNER_PASSWORD es obligatorio}"

        php artisan migrate --force
        php artisan laravelcrm:install \
            --production \
            --no-interaction \
            --owner-name="${CRM_OWNER_NAME}" \
            --owner-email="${CRM_OWNER_EMAIL}" \
            --owner-password="${CRM_OWNER_PASSWORD}"
        date -u +"%Y-%m-%dT%H:%M:%SZ" > "${BOOTSTRAP_MARKER}"
    fi

    prime_caches
}

bootstrap_worker() {
    ensure_runtime_key
    wait_for_db
    wait_for_redis

    echo "Esperando bootstrap inicial del CRM..."
    while [ ! -f "${BOOTSTRAP_MARKER}" ]; do
        sleep 2
    done

    prime_caches
}

ensure_runtime_key

cd "${APP_DIR}"

if [ "${ROLE}" = "web" ]; then
    bootstrap_web
else
    bootstrap_worker
fi
