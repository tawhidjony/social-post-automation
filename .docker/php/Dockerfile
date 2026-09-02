FROM php:8.5-fpm-bookworm

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl ca-certificates gnupg unzip zip \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key \
       | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_24.x nodistro main" \
       > /etc/apt/sources.list.d/nodesource.list \
    && apt-get update && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    pdo_mysql pcntl zip bcmath intl gd opcache redis sockets

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY entrypoint.sh /usr/local/bin/entrypoint

WORKDIR /src

RUN chmod +x /usr/local/bin/entrypoint \
    && groupadd -g 1000 tawhidjony \
    && useradd -u 1000 -g tawhidjony -m tawhidjony \
    && chown -R tawhidjony:tawhidjony /src

USER tawhidjony

ENTRYPOINT ["/usr/local/bin/entrypoint"]
