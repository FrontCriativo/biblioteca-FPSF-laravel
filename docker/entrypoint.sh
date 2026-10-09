#!/bin/sh
set -e

# Roda os comandos de preparação como o usuário "app" (mesmo UID do host).
as_app() {
    runuser -u app -- "$@"
}

if [ ! -f vendor/autoload.php ]; then
    echo "> Instalando dependências do Composer..."
    as_app composer install --no-interaction --prefer-dist
fi

if [ ! -f .env ]; then
    echo "> Criando .env a partir do .env.example..."
    as_app cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    echo "> Gerando APP_KEY..."
    as_app php artisan key:generate --force
fi

if grep -q '^DB_CONNECTION=sqlite' .env && [ ! -f database/database.sqlite ]; then
    echo "> Criando banco SQLite e populando com dados de exemplo..."
    as_app touch database/database.sqlite
    as_app php artisan migrate --seed --force
else
    as_app php artisan migrate --force
fi

exec "$@"
