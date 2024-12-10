FROM php:8.1-apache

# Installera mysqli (MySQL-tillägget för PHP)
RUN docker-php-ext-install mysqli

# Kopiera webbappen till /var/www/html
COPY ./html /var/www/html

# Aktivera Apache mod_rewrite (valfritt för vissa applikationer)
RUN a2enmod rewrite

# Sätt ServerName för att undvika varningar
RUN echo "ServerName localhost" >> /etc/apache2/conf-available/servername.conf && \
    a2enconf servername
