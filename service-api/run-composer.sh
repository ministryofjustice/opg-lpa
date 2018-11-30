# Execute this inside the docker container
if [ ! -e /tmp/composer.phar ]
then
  cd /tmp
  curl -s https://getcomposer.org/installer | php
fi
cd /app
mkdir -p /app/vendor
chown -R app:app /app/vendor
gosu app php /tmp/composer.phar install --prefer-dist --optimize-autoloader --no-suggest --no-interaction --no-scripts
