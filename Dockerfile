FROM php:8.4-apache

# Install dependencies sistem
RUN apt-get update && apt-get install -y \
    git \
    curl \
    pkg-config \
    libffi-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql zip bcmath exif pcntl opcache ffi \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Aktifkan mod_rewrite Apache biar routing Laravel gak 404
RUN a2enmod rewrite

# Arahkan DocumentRoot ke folder /public-nya Laravel
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Aktifkan FFI dan fix git dubious ownership
RUN echo "ffi.enable=true" > /usr/local/etc/php/conf.d/ffi.ini
RUN git config --global --add safe.directory /var/www/html

# Pasang Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Pasang Node.js
RUN apt-get update && apt-get install -y ca-certificates gnupg && \
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy semua file project
COPY . .

# Jalankan instalasi library & build asset
RUN composer install --no-interaction --optimize-autoloader
RUN npm install && npm run build

# Set permission
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80
