FROM php:8.2-apache

# 1. Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libpq-dev libzip-dev zip unzip git \
    && docker-php-ext-install pdo pdo_mysql

# 2. Configurar Apache (Esto es CLAVE para que Laravel funcione en Render)
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN a2enmod rewrite

# 3. Copiar archivos del proyecto
COPY . /var/www/html

# 4. Instalar Composer y dependencias
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader

# 5. Permisos correctos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 6. Forzar limpieza de caché durante la construcción
RUN php artisan config:clear

# Render usa el puerto 80 por defecto en Apache
EXPOSE 80

# 7. El comando de inicio CORRECTO para Apache
# Quitamos "php artisan serve" porque da problemas de caché en Docker
CMD ["apache2-foreground"]