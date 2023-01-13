FROM insready/drupal-commerce:latest

ARG GITHUB_TOKEN

COPY docker/drupal/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
RUN apt update
RUN apt-get install git mariadb-client unzip nano -y
RUN git clone https://github.com/vishnubob/wait-for-it.git /wait-for-it/
RUN cp /wait-for-it/wait-for-it.sh /usr/local/bin/wait-for-it
WORKDIR /opt
RUN rm -rf drupal
RUN composer config -g github-oauth.github.com ${GITHUB_TOKEN}
RUN composer create-project drupalcommerce/demo-project drupal --stability dev --no-interaction --no-install
WORKDIR /opt/drupal
RUN composer config allow-plugins.composer/installers true
RUN composer config allow-plugins.cweagans/composer-patches true
RUN composer config allow-plugins.drupal/core-composer-scaffold true
RUN composer config allow-plugins.composer/installers true
RUN composer config allow-plugins.zaporylie/composer-drupal-optimizations true
RUN composer config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
RUN composer config allow-plugins.oomphinc/composer-installers-extender true
RUN composer config allow-plugins.drupal/console-extend-plugin true
RUN composer install --no-interaction
RUN composer require drupal/console:@stable --prefer-dist --optimize-autoloader --sort-packages --with-all-dependencies --no-interaction --no-update
RUN composer update --no-interaction
RUN git clone https://oauth2:${GITHUB_TOKEN}@github.com/MultiSafepay/drupal-expose.git ./drupal_expose/
RUN chown -R www-data:www-data ./
