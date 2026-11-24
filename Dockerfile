FROM php:8.3-apache

# PDO PostgreSQL driver (config.php uses PDO with pgsql)
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev \
 && docker-php-ext-install pdo_pgsql \
 && apt-get purge -y --auto-remove libpq-dev && apt-get install -y --no-install-recommends libpq5 \
 && rm -rf /var/lib/apt/lists/* \
 && a2enmod rewrite headers

# Production PHP settings; evidence uploads are capped at 3 MB each in report.php
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && { \
      echo 'upload_max_filesize=10M'; \
      echo 'post_max_size=12M'; \
      echo 'expose_php=Off'; \
      echo 'date.timezone=Africa/Nairobi'; \
    } > "$PHP_INI_DIR/conf.d/app.ini"

# Render sends traffic to $PORT (default 10000). Make Apache listen on it.
ENV PORT=10000
RUN sed -ri 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
 && sed -ri 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' /etc/apache2/sites-available/000-default.conf

# Block web access to files that must never be served.
RUN { \
      echo 'ServerTokens Prod'; \
      echo 'ServerSignature Off'; \
      echo '<Directory /var/www/html/uploads>'; \
      echo '  php_admin_flag engine off'; \
      echo '</Directory>'; \
      echo '<FilesMatch "^(install|init_db)\.php$|\.sql$">'; \
      echo '  Require all denied'; \
      echo '</FilesMatch>'; \
    } > /etc/apache2/conf-available/app-security.conf \
 && a2enconf app-security

WORKDIR /var/www/html
COPY . .

RUN mkdir -p uploads \
 && chown -R www-data:www-data uploads

EXPOSE 10000
# Create the database tables on first start, then run Apache
CMD ["sh", "-c", "php /var/www/html/init_db.php && exec apache2-foreground"]
