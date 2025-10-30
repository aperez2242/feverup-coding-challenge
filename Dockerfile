# Use the official WordPress image as base
FROM wordpress:php8.2-apache

# Install additional PHP extensions commonly used in WP dev
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    vim \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    subversion \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mysqli

# Enable Apache mod_rewrite for permalinks
RUN a2enmod rewrite

# Ensure .htaccess rules are respected
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Working directory
WORKDIR /var/www/html

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

# Install Composer (for PHPUnit + plugins)
RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

# Install WordPress-compatible PHPUnit + Polyfills
RUN composer global remove phpunit/phpunit --quiet || true \
    && composer global remove yoast/phpunit-polyfills --quiet || true \
    && composer global require phpunit/phpunit:^9.6 yoast/phpunit-polyfills:^2.0 --update-with-all-dependencies \
    && ln -sf /root/.composer/vendor/bin/phpunit /usr/local/bin/phpunit

# Install WP-CLI for debugging and automation
RUN curl -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x /usr/local/bin/wp