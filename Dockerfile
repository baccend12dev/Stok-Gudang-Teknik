# Gunakan image PHP 5.6 dengan Apache dari webdevops
FROM webdevops/php-apache:5.6

# Aktifkan mod_rewrite untuk .htaccess Laravel (sudah aktif di image ini, tapi tetap aman)
RUN a2enmod rewrite || true

# Set working directory
WORKDIR /app

# Ubah dokumen root Apache ke folder public Laravel
ENV WEB_DOCUMENT_ROOT=/app/public

# Salin isi project ke dalam container
COPY . .

# Berikan izin akses folder storage dan bootstrap/cache
RUN chown -R application:application /app/storage /app/bootstrap/cache || true
