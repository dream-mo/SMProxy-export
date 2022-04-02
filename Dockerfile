FROM composer:2.2.4 as composer
FROM dreammo/smproxy:v1.3.1
COPY --from=composer /usr/bin/composer /usr/bin
COPY ./metrics/sh/docker-entrypoint.sh /app/metrics/sh/docker-entrypoint.sh
COPY ./metrics/index.php  /app/metrics
COPY ./metrics/SMProxyMetrics.php /app/metrics/
WORKDIR /app/metrics
RUN composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/ \
    && composer require promphp/prometheus_client_php && composer install
WORKDIR /usr/local/smproxy/
#smproxy listen port
EXPOSE  3366
#smproxy exporter listen port
EXPOSE  9137
ENTRYPOINT ["/bin/sh", "-c", "/app/metrics/sh/docker-entrypoint.sh"]
