# ========================================================
# BazarKinun.com — Production Dockerfile for Render.com
# ========================================================
FROM php:8.2-apache

# Install system dependencies & libraries for PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libzip-dev \
    zip \
    unzip \
    ca-certificates \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Update CA root certificates for secure SSL connections (TiDB, httpSMS)
RUN update-ca-certificates

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        gd \
        zip \
        opcache

# Enable Apache modules required for .htaccess routing, caching and security
RUN a2enmod rewrite headers expires deflate

# Recommended production PHP configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "memory_limit = 256M" >> "$PHP_INI_DIR/php.ini" \
    && echo "upload_max_filesize = 25M" >> "$PHP_INI_DIR/php.ini" \
    && echo "post_max_size = 28M" >> "$PHP_INI_DIR/php.ini" \
    && echo "max_execution_time = 60" >> "$PHP_INI_DIR/php.ini" \
    && echo "display_errors = Off" >> "$PHP_INI_DIR/php.ini" \
    && echo "log_errors = On" >> "$PHP_INI_DIR/php.ini" \
    && echo "opcache.enable=1" >> "$PHP_INI_DIR/php.ini" \
    && echo "opcache.validate_timestamps=0" >> "$PHP_INI_DIR/php.ini" \
    && echo "opcache.memory_consumption=128" >> "$PHP_INI_DIR/php.ini"

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Create uploads directory structure and set proper ownership & permissions
RUN mkdir -p /var/www/html/uploads/lists \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

# Configure Apache virtual host to allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Render dynamically passes $PORT (default 10000 or 80)
# Create a startup wrapper script to bind Apache to $PORT and populate .env with runtime environment variables
RUN echo '#!/bin/bash\n\
PORT_TO_USE="${PORT:-80}"\n\
sed -i "s/80/${PORT_TO_USE}/g" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf\n\
env | grep -E "^(DB_|APP_|CURL_|PORT)" > /var/www/html/.env\n\
chown www-data:www-data /var/www/html/.env\n\
chmod 644 /var/www/html/.env\n\
exec apache2-foreground' > /usr/local/bin/start-server.sh \
    && chmod +x /usr/local/bin/start-server.sh

# Expose default port
EXPOSE 80 10000

# Start server via startup script
CMD ["/usr/local/bin/start-server.sh"]

