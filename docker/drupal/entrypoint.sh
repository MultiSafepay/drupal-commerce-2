#!/bin/sh

wait-for-it -t 60 mysql:3306 -- echo "Installation can proceed"
cd /opt/drupal
php -d memory_limit=-1 bin/drush si demo_commerce --db-url=mysql://root:example@mysql:3306/drupal --account-pass=admin -y -vvv
php bin/drupal commerce:create:store --name=EuroShop --currency=EUR --country=NL --mail=integration@multisafepay.com
mysql 'drupal' -u 'root' -p'example' -h 'mysql' -e "UPDATE commerce_product_variation_field_data SET price__currency_code = 'EUR'"

# Loop through all shipping methods and replace USD with EUR
mysql 'drupal' -u 'root' -p'example' -h 'mysql' -e "SELECT plugin__target_plugin_configuration FROM commerce_shipping_method_field_data;" | while read plugin__target_plugin_configuration; do
    result=$(echo "$plugin__target_plugin_configuration" | sed "s/USD/EUR/g")
    mysql 'drupal' -u 'root' -p'example' -h 'mysql' -e "UPDATE commerce_shipping_method_field_data SET plugin__target_plugin_configuration = '${result}' WHERE plugin__target_plugin_configuration = '${plugin__target_plugin_configuration}';"
done

# Delete conditional showing of the payment method
mysql 'drupal' -u 'root' -p'example' -h 'mysql' -e "DELETE FROM commerce_shipping_method__conditions;"

php -d memory_limit=-1 bin/drupal moi commerce_multisafepay_payments
php bin/drush cr
docker-php-entrypoint apache2-foreground
