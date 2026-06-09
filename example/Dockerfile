FROM hyperf/hyperf:8.3-alpine-v3.19-swoole-v5.1

WORKDIR /opt/www

COPY . /opt/www

WORKDIR /opt/www/example

RUN composer install --no-dev -o && \
    mkdir -p runtime/logs && \
    php bin/hyperf.php

EXPOSE 9501 9502

CMD ["php", "bin/hyperf.php", "start"]
