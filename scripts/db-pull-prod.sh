#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# db-pull-prod.sh  —  Dump production DB and load into local dev container
#
# Usage:
#   ./scripts/db-pull-prod.sh              # dump + import
#   ./scripts/db-pull-prod.sh --dump-only  # dump to file, skip import
#   ./scripts/db-pull-prod.sh --import-only # import last dump, skip new dump
#   ./scripts/db-pull-prod.sh --db-name mydb # restore into custom local DB
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

# ── Production connection ────────────────────────────────────────────────────
PROD_HOST="db-a09dc47f-9679-4280-a099-25ad8ecb1410.us-east-2.public.db.laravel.cloud"
PROD_PORT="3306"
PROD_DB="main"
PROD_USER="zvdlt2gy8s82unuy"
PROD_PASS="BcleC69YdE41Lv1t0A2J"

# ── Local dev connection (matches docker-compose.yml) ───────────────────────
LOCAL_CONTAINER="accounts_mysql"
LOCAL_DB="accounts"
LOCAL_USER="laravel"
LOCAL_PASS="laravel"
LOCAL_ADMIN_USER="root"
LOCAL_ADMIN_PASS="root"
LOCAL_HOST="127.0.0.1"
LOCAL_PORT="3307"

ENV_FILE="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)/.env"

# ── Dump file ────────────────────────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DUMP_DIR="$SCRIPT_DIR/../storage/db-dumps"
DUMP_FILE="$DUMP_DIR/prod-$(date +%Y%m%d-%H%M%S).sql"
LATEST_LINK="$DUMP_DIR/latest.sql"

mkdir -p "$DUMP_DIR"

DUMP_ONLY=false
IMPORT_ONLY=false
CUSTOM_DB_NAME=""

usage() {
  echo "Usage:"
  echo "  ./scripts/db-pull-prod.sh"
  echo "  ./scripts/db-pull-prod.sh --dump-only"
  echo "  ./scripts/db-pull-prod.sh --import-only"
  echo "  ./scripts/db-pull-prod.sh --db-name <database_name>"
}

upsert_env_key() {
  local key="$1"
  local value="$2"

  if grep -qE "^${key}=" "$ENV_FILE"; then
    sed -i.bak "s|^${key}=.*|${key}=${value}|" "$ENV_FILE"
  else
    echo "${key}=${value}" >> "$ENV_FILE"
  fi
}

update_app_env_db_settings() {
  if [ ! -f "$ENV_FILE" ]; then
    echo "⚠  .env not found at $ENV_FILE. Skipping env update."
    return
  fi

  upsert_env_key "DB_CONNECTION" "mysql"
  upsert_env_key "DB_HOST" "$LOCAL_HOST"
  upsert_env_key "DB_PORT" "$LOCAL_PORT"
  upsert_env_key "DB_DATABASE" "$LOCAL_DB"
  upsert_env_key "DB_USERNAME" "$LOCAL_USER"
  upsert_env_key "DB_PASSWORD" "$LOCAL_PASS"

  rm -f "${ENV_FILE}.bak"
  echo "✔  Updated app DB settings in $ENV_FILE"
}

while [ $# -gt 0 ]; do
  case "$1" in
    --dump-only)
      DUMP_ONLY=true
      shift
      ;;
    --import-only)
      IMPORT_ONLY=true
      shift
      ;;
    --db-name)
      if [ -z "${2:-}" ]; then
        echo "✘  Missing value for --db-name" >&2
        usage
        exit 1
      fi
      CUSTOM_DB_NAME="$2"
      shift 2
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "✘  Unknown argument: $1" >&2
      usage
      exit 1
      ;;
  esac
done

if [ -n "$CUSTOM_DB_NAME" ]; then
  LOCAL_DB="$CUSTOM_DB_NAME"
fi

if [ "$DUMP_ONLY" = true ] && [ "$IMPORT_ONLY" = true ]; then
  echo "✘  --dump-only and --import-only cannot be used together." >&2
  exit 1
fi

# ── Step 1: Dump from production ─────────────────────────────────────────────
if [ "$IMPORT_ONLY" = false ]; then
  echo ""
  echo "▶  Dumping production database '$PROD_DB'..."
  echo "   Host : $PROD_HOST:$PROD_PORT"
  echo "   File : $DUMP_FILE"
  echo ""

  docker exec "$LOCAL_CONTAINER" \
    mysqldump \
      --host="$PROD_HOST" \
      --port="$PROD_PORT" \
      --user="$PROD_USER" \
      --password="$PROD_PASS" \
      --single-transaction \
      --no-tablespaces \
      --set-gtid-purged=OFF \
      --column-statistics=0 \
      "$PROD_DB" > "$DUMP_FILE"

  # Update the "latest" symlink
  ln -sf "$(basename "$DUMP_FILE")" "$LATEST_LINK"
  echo "✔  Dump saved: $DUMP_FILE"
fi

# ── Step 2: Import into local dev DB ─────────────────────────────────────────
if [ "$DUMP_ONLY" = false ]; then
  # Resolve which file to import
  if [ "$IMPORT_ONLY" = true ]; then
    if [ ! -f "$LATEST_LINK" ]; then
      echo "✘  No dump found at $LATEST_LINK — run without --import-only first." >&2
      exit 1
    fi
    DUMP_FILE="$(readlink -f "$LATEST_LINK")"
    echo ""
    echo "▶  Importing existing dump: $DUMP_FILE"
  fi

  echo ""
  echo "▶  Importing into local '$LOCAL_DB'..."

  # Drop and recreate the local database using admin credentials.
  docker exec "$LOCAL_CONTAINER" \
    mysql --user="$LOCAL_ADMIN_USER" --password="$LOCAL_ADMIN_PASS" \
    --execute="DROP DATABASE IF EXISTS \`$LOCAL_DB\`; CREATE DATABASE \`$LOCAL_DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

  # Ensure the app user can access the restored database.
  docker exec "$LOCAL_CONTAINER" \
    mysql --user="$LOCAL_ADMIN_USER" --password="$LOCAL_ADMIN_PASS" \
    --execute="GRANT ALL PRIVILEGES ON \`$LOCAL_DB\`.* TO '$LOCAL_USER'@'%'; FLUSH PRIVILEGES;"

  # Stream the dump file into the container and import using admin credentials.
  docker exec -i "$LOCAL_CONTAINER" \
    mysql --user="$LOCAL_ADMIN_USER" --password="$LOCAL_ADMIN_PASS" "$LOCAL_DB" < "$DUMP_FILE"

  echo "✔  Import complete."
  echo ""
  echo "──────────────────────────────────────────────"
  echo "  Local DB : $LOCAL_DB (port $LOCAL_PORT)"
  echo "  Run      : php artisan migrate  (if needed)"
  echo "──────────────────────────────────────────────"

  update_app_env_db_settings
fi
