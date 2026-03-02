# Dockerfile pour WCDO Backend

FROM php:8.2-fpm-alpine

# Installer les dépendances
RUN apk add --no-cache \
    zip \
    curl \
    git \
    mysql-client \
    && docker-php-ext-install pdo pdo_mysql

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /app

# Copier les fichiers
COPY . /app

# Installer les dépendances Composer
RUN composer install --no-dev --optimize-autoloader

# Expose port
EXPOSE 9000

# Commande par défaut
CMD ["php-fpm"]
