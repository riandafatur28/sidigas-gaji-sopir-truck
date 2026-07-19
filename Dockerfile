# ============================================
# BUILD STAGE: Install deps & build assets
# ============================================
FROM php:8.3-cli AS build

RUN apt-get update && apt-get install -y \
    git unzip zip libpng-dev libxml2-dev libonig-dev libcurl4-openssl-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql pdo_sqlite mbstring xml gd curl \
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
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY package.json package-lock.json ./
RUN npm ci --omit=dev

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
    libpng-dev libxml2-dev libonig-dev libcurl4-openssl-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql pdo_sqlite mbstring xml gd curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=build /app /app
COPY --from=build /usr/bin/composer /usr/bin/composer

RUN chmod -R 775 storage bootstrap/cache \
    && mkdir -p /app/database \
    && chmod -R 777 /app/database

EXPOSE ${PORT:-8080}

RUN ln -sf /app/storage/app/public /app/public/storage 2>/dev/null || true

CMD cp .env.example .env 2>/dev/null; \
    php artisan key:generate --force \
    && php artisan migrate --force --isolated \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
