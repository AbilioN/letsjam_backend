FROM php:8.2-fpm

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    nodejs \
    npm \
    supervisor \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Instalar Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Criar usuário para a aplicação
RUN useradd -G www-data,root -u 1000 -d /home/akerfeels akerfeels
RUN mkdir -p /home/akerfeels/.composer && \
    chown -R akerfeels:akerfeels /home/akerfeels

# Definir diretório de trabalho
WORKDIR /var/www

# Copiar arquivos do projeto
COPY . /var/www

# Instalar dependências do PHP
RUN composer install --no-dev --optimize-autoloader

# Instalar dependências do Node.js
RUN npm install

# Construir assets
RUN npm run build

# Definir permissões
RUN chown -R akerfeels:akerfeels /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Mudar para o usuário akerfeels
USER akerfeels

# Expor porta 9000
EXPOSE 9000

# Comando padrão
CMD ["php-fpm"] 