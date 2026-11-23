FROM php:8.2-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN a2enmod rewrite
RUN mkdir -p /var/www/html/uploads && chown -R www-data:www-data /var/www/html/uploads && chmod -R 777 /var/www/html/uploads
COPY . /var/www/html/
RUN echo '<Directory /var/www/html>\nAllowOverride All\n</Directory>' >> /etc/apache2/apache2.conf