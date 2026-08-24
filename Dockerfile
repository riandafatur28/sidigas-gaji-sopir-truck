# ============================================
# BUILD STAGE: Install deps & build assets
# ============================================
FROM php:8.3-cli AS build

RUN apt-get update && apt-get install -y \
    git unzip zip libpng-dev libxml2-dev libonig-dev libcurl4-openssl-dev libsqlite3-dev libzip-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite mbstring xml gd curl zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Node
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Create dummy .env for build scripts
RUN echo "APP_KEY=base64:dummykeyforbuild" > /app/.env && echo "DB_CONNECTION=sqlite" >> /app/.env

# Copy dependency manifests first (cache layer)
COPY composer.json composer.lock artisan ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY package.json package-lock.json ./
RUN npm ci

# Copy rest of app
COPY . .

# Build frontend
RUN npm run build

# Clean up dummy env
RUN rm -f /app/.env

# ============================================
# RUN STAGE: Minimal image
# ============================================
FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    libpng-dev libxml2-dev libonig-dev libcurl4-openssl-dev libsqlite3-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite mbstring xml gd curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=build /app /app
COPY --from=build /usr/bin/composer /usr/bin/composer

RUN chmod -R 775 storage bootstrap/cache \
    && mkdir -p /app/database \
    && chmod -R 777 /app/database

# Ensure public/storage exists (direct folder, not symlink — symlinks break in Docker)
RUN mkdir -p /app/public/storage/bukti \
    && cp -rn /app/storage/app/public/* /app/public/storage/ 2>/dev/null || true \
    && chmod -R 777 /app/public/storage

EXPOSE ${PORT:-8080}

CMD mkdir -p /app/public/storage/bukti && chmod -R 777 /app/public/storage; \
    if [ ! -f .env ]; then cp .env.example .env; fi; \
    sed -i "s/^APP_KEY=.*/APP_KEY=${APP_KEY}/" .env 2>/dev/null || echo "APP_KEY=${APP_KEY}" >> .env; \
    sed -i "s/^APP_ENV=.*/APP_ENV=local/" .env 2>/dev/null || echo "APP_ENV=local" >> .env; \
    sed -i "s/^APP_DEBUG=.*/APP_DEBUG=true/" .env 2>/dev/null || echo "APP_DEBUG=true" >> .env; \
    sed -i "s/^LOG_CHANNEL=.*/LOG_CHANNEL=stderr/" .env 2>/dev/null || echo "LOG_CHANNEL=stderr" >> .env; \
    php artisan migrate --force --isolated \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
