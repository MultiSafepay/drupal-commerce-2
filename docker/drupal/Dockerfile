FROM insready/drupal-commerce

COPY docker/drupal/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
RUN apt update
RUN apt-get install git mariadb-client -y
RUN git clone https://github.com/vishnubob/wait-for-it.git /wait-for-it/
RUN cp /wait-for-it/wait-for-it.sh /usr/local/bin/wait-for-it
WORKDIR /opt
RUN rm -r drupal
RUN composer create-project drupalcommerce/demo-project drupal --stability dev --no-interaction
WORKDIR /opt/drupal
RUN composer require drush/drush:^10.0 drupal/console -W
