FROM php:8.1-apache

# تثبيت SQLite3 والإضافات المطلوبة
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite

# تثبيت curl إذا لم يكن موجوداً
RUN apt-get install -y curl

# نسخ ملفات التطبيق
COPY . /var/www/html/

# إعطاء صلاحيات لقاعدة البيانات
RUN chmod 755 /var/www/html/
RUN touch /var/www/html/users.db
RUN chmod 666 /var/www/html/users.db

# تفعيل mod_rewrite
RUN a2enmod rewrite

# تعيين DocumentRoot
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

EXPOSE 80