# Tasuku 開発用イメージ（app / node の両サービスで共用する）
#
# node サービスにも PHP を入れているのは、Wayfinder の Vite プラグインが
# ビルド中に `php artisan wayfinder:generate` を呼び出すため。
FROM php:8.5-cli

# Composer の依存解決と Node のセットアップに必要な OS パッケージ
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip curl ca-certificates gnupg \
    && rm -rf /var/lib/apt/lists/*

# PHP 拡張（ビルド依存を自動で入れて後片付けしてくれる installer を使う）
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql intl zip bcmath pcntl

# Composer 2
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Node.js 24 LTS（Vite+ の要件を満たすため、ホストの Node は使わない）
RUN curl -fsSL https://deb.nodesource.com/setup_24.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# 開発用の php.ini（本番用の設定は使わない）
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" \
    && echo "memory_limit=512M" > "$PHP_INI_DIR/conf.d/tasuku.ini"

WORKDIR /var/www/html
