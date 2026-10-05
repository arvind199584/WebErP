# ========================================================
# ModPyPhp Enterprise ERP Container
# Multi-runtime: PHP 8.3 CLI + Python 3.11 + Supervisor
# ========================================================

FROM php:8.3-cli-bookworm

# Set environment variables
ENV DEBIAN_FRONTEND=noninteractive \
    PYTHONUNBUFFERED=1 \
    PYTHONDONTWRITEBYTECODE=1 \
    PATH="/opt/venv/bin:$PATH"

WORKDIR /var/www/html

# 1. Install system dependencies, PostgreSQL client libraries, Python 3, and Supervisor
RUN apt-get update && apt-get install -y --no-install-recommends \
    bash \
    curl \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    python3 \
    python3-pip \
    python3-venv \
    python3-dev \
    build-essential \
    supervisor \
    procps \
    net-tools \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        gd \
        zip \
        mbstring \
        xml \
        fileinfo \
        curl \
        bcmath \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 2. Install Composer from official image
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# 3. Setup Python Virtual Environment and install dependencies
RUN python3 -m venv /opt/venv
COPY requirements.txt ./
RUN pip install --no-cache-dir -U pip setuptools wheel \
    && pip install --no-cache-dir -r requirements.txt

# 4. Copy Composer manifests and install PHP dependencies
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# 5. Copy application source code
COPY . .

# 6. Generate optimized Composer autoloader
RUN composer dump-autoload --optimize

# 7. Copy custom PHP configuration, supervisor configuration, and entrypoint
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# 8. Setup permissions and line endings
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && mkdir -p sessions_new storage/sessions storage/backups storage/templates logs \
    && chmod -R 777 sessions_new storage logs

# Expose service ports (Render dynamically assigns $PORT)
EXPOSE 8000 10000 5000 5001

# Healthcheck to verify PHP web server is responding (supports dynamic $PORT)
HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD sh -c "curl -f http://localhost:\${PORT:-8000}/ || exit 1"

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord"]
