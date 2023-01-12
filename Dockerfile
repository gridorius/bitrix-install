FROM alpine

COPY index.php /data/

ENTRYPOINT cp /data/index.php /var/www/html/