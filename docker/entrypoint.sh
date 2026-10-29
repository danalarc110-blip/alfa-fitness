#!/bin/sh
set -eu

APP_DIR=/var/www/html
DB_CONNECTION="${DB_CONNECTION:-pgsql}"
case "$DB_CONNECTION" in
    pgsql) DEFAULT_DATA_DIR=/tmp/alpha-data ;;
    sqlite) DEFAULT_DATA_DIR=/var/data ;;
    *) echo 'Esta imagen admite PostgreSQL o SQLite con disco persistente.' >&2; exit 1 ;;
esac
DATA_DIR="${ALPHA_DATA_DIR:-$DEFAULT_DATA_DIR}"
PORT="${PORT:-10000}"
export ALPHA_DATA_DIR="$DATA_DIR" DB_CONNECTION PORT

: "${APP_KEY:?Configura la APP_KEY original antes de iniciar Alpha Fitness.}"
APP_URL="$(php -r '
    $url = getenv("APP_URL") ?: getenv("RENDER_EXTERNAL_URL");
    $parts = $url ? parse_url($url) : false;
    if (!is_array($parts) || ($parts["scheme"] ?? null) !== "https"
        || empty($parts["host"]) || isset($parts["user"]) || isset($parts["pass"])
        || isset($parts["query"]) || isset($parts["fragment"])) {
        fwrite(STDERR, "Configura APP_URL o RENDER_EXTERNAL_URL con la URL HTTPS pública.\n");
        exit(1);
    }
    echo rtrim($url, "/");
')"
export APP_URL
case "$PORT" in ''|*[!0-9]*) echo 'PORT debe ser numérico.' >&2; exit 1 ;; esac
if [ "$PORT" -lt 1 ] || [ "$PORT" -gt 65535 ]; then
    echo 'PORT está fuera del rango permitido.' >&2
    exit 1
fi
if [ "$DB_CONNECTION" = pgsql ]; then
    if [ "$DATA_DIR" != /tmp/alpha-data ]; then
        echo 'El modo PostgreSQL usa /tmp/alpha-data para sus archivos locales de demostración.' >&2
        exit 1
    fi
    php -r '
        $parts = parse_url(getenv("DB_URL") ?: "");
        if (!is_array($parts) || !in_array($parts["scheme"] ?? "", ["postgres", "postgresql", "pgsql"], true)
            || empty($parts["host"]) || empty(trim($parts["path"] ?? "", "/"))) {
            fwrite(STDERR, "Configura DB_URL con la conexión PostgreSQL de Render.\n");
            exit(1);
        }
    '
else
    if [ "$DATA_DIR" != /var/data ] \
        || [ "${DB_DATABASE:-/var/data/database.sqlite}" != "$DATA_DIR/database.sqlite" ]; then
        echo 'El modo SQLite requiere la base /var/data/database.sqlite en un disco persistente.' >&2
        exit 1
    fi
    if [ -n "${DB_URL:-}" ]; then
        echo 'DB_URL debe estar vacío en el modo SQLite.' >&2
        exit 1
    fi
    if ! mountpoint -q "$DATA_DIR"; then
        echo 'No hay un disco persistente montado en /var/data. No se usará SQLite efímero.' >&2
        exit 1
    fi
fi

cd "$APP_DIR"
mkdir -p "$DATA_DIR/private" "$DATA_DIR/images" storage/app storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs bootstrap/cache .seed-images

php artisan config:clear
if [ "$DB_CONNECTION" = pgsql ]; then
    # Create or upgrade the schema before importing portable table rows.
    php artisan migrate --force --no-interaction
    php "$APP_DIR/docker/bootstrap-data.php"
else
    # SQLite imports an intact database file before applying pending migrations.
    php "$APP_DIR/docker/bootstrap-data.php"
    if [ ! -s "$DATA_DIR/database.sqlite" ]; then
        echo 'La base importada no existe o está vacía. No se creará una base vacía.' >&2
        exit 1
    fi
    php artisan migrate --force --no-interaction
fi
# Keep the bootstrap payload out of the web worker environment after import.
for bootstrap_variable in $(printenv | sed -n 's/^\(ALPHA_BOOTSTRAP_[A-Z0-9_]*\)=.*/\1/p'); do
    unset "$bootstrap_variable"
done
link_runtime_directory() {
    source_directory="$1"
    persistent_directory="$2"
    seed_directory="$3"
    mkdir -p "$persistent_directory"
    if [ -L "$source_directory" ]; then
        if [ "$(readlink "$source_directory")" != "$persistent_directory" ]; then
            echo 'Se detectó un enlace de almacenamiento inesperado.' >&2
            exit 1
        fi
        return
    fi
    if [ -d "$source_directory" ]; then
        # Copy only missing shipped assets. Never replace an imported upload.
        cp -an "$source_directory/." "$persistent_directory/"
        if [ -e "$seed_directory" ]; then
            echo 'El directorio de respaldo inicial ya existe; no se sobrescribirá.' >&2
            exit 1
        fi
        mv "$source_directory" "$seed_directory"
    fi
    ln -s "$persistent_directory" "$source_directory"
}

for directory in avatars productos ejercicios; do
    link_runtime_directory "$APP_DIR/public/images/$directory" \
        "$DATA_DIR/images/$directory" "$APP_DIR/.seed-images/$directory"
done
link_runtime_directory "$APP_DIR/storage/app/private" "$DATA_DIR/private" "$APP_DIR/.seed-images/private"

chown -R www-data:www-data "$DATA_DIR" storage bootstrap/cache
chmod 700 "$DATA_DIR/private"
if [ "$DB_CONNECTION" = sqlite ]; then
    chmod 600 "$DATA_DIR/database.sqlite"
fi
php artisan config:cache
php artisan view:cache

printf 'Listen %s\n' "$PORT" > /etc/apache2/ports.conf
sed -e "s/__PORT__/$PORT/g" -e "s#__DATA_DIR__#$DATA_DIR#g" \
    docker/apache-vhost.conf > /etc/apache2/sites-available/000-default.conf
apache2ctl configtest

exec "$@"
