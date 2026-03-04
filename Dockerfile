FROM dunglas/frankenphp:php8.2.30-bookworm

# تثبيت الامتداد gd
RUN install-php-extensions gd

# تثبيت الامتداد zip
RUN install-php-extensions zip

# تثبيت أدوات النظام: git + unzip
RUN apt-get update && apt-get install -y git unzip

# إضافة Composer من الصورة الرسمية
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer



WORKDIR /app
COPY . .

RUN composer install --optimize-autoloader --no-interaction

