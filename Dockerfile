FROM php:8.2-apache

# Install PostgreSQL PDO driver
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Install mysqli too (in case you need it later)
RUN docker-php-ext-install mysqli

# Copy all your project files into the container
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html/

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Apache config
RUN echo '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

EXPOSE 80