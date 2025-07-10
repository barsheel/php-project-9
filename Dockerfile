FROM php:8.2-cli

# Установка зависимостей
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    unzip \
    zip \
    curl \
    git \
    libpq-dev \
    libxml2-dev \
    libzip-dev \
    libonig-dev
	
# Устанавливаем системные зависимости для sqlite3
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

# Включаем PDO и SQLite3
RUN docker-php-ext-install pdo pdo_sqlite

# Если нужно именно sqlite3 (не PDO), используем:
# RUN docker-php-ext-install sqlite3

RUN docker-php-ext-install    bcmath
RUN docker-php-ext-install    exif
RUN docker-php-ext-install    dom
RUN docker-php-ext-install    xml
RUN docker-php-ext-install    mbstring
RUN docker-php-ext-install    zip
RUN apt-get clean && rm -rf /var/lib/apt/lists/*



RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"


# Экспортируем порт приложения
EXPOSE 8000

#лучше если соответствует названию пакета, но не обязательно (App - вероятно вызовет конфликт)
WORKDIR /hexlet-code

COPY . .

#только для деплоя
RUN composer install || true

#запуск

CMD ["bash", "-c", "make start"]