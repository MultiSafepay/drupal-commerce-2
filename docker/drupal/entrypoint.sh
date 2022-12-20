#!/bin/sh

# List of local paths to use into the container
path_base='/opt/drupal'
path_drush="$path_base"/bin/drush
path_drupal="$path_base"/vendor/drupal/console/bin/drupal
path_image_origin="$path_base"/web/sites/default/files
path_image_catalog="$path_image_origin"/styles/catalog/public
path_image_medium="$path_image_origin"/styles/medium/public

wait-for-it -t 60 mysql:3306

if mysql -u 'root' -p'example' -h 'mysql' -e 'USE drupal; SELECT * FROM commerce_payment_method LIMIT 1' 2>/dev/null; then
  docker-php-entrypoint apache2-foreground
  return 0
fi

echo 'Installation started'
php -d memory_limit=-1 "$path_drush" si demo_commerce --db-url=mysql://root:example@mysql:3306/drupal --account-pass=admin --site-name=EuroShop --uri="$1" -y -vvv
php "$path_drupal" commerce:create:store --name=EuroShop --mail=integration@multisafepay.com --country=NL --currency=EUR
mysql 'drupal' -u 'root' -p'example' -h 'mysql' -e "UPDATE commerce_product_variation_field_data SET price__currency_code = 'EUR'"

# Loop through all shipping methods and replace USD with EUR
# shellcheck disable=SC2162
mysql 'drupal' -u 'root' -p'example' -h 'mysql' -e 'SELECT plugin__target_plugin_configuration FROM commerce_shipping_method_field_data;' | while read plugin__target_plugin_configuration; do
    result=$(echo "$plugin__target_plugin_configuration" | sed 's/USD/EUR/g')
    mysql 'drupal' -u 'root' -p'example' -h 'mysql' -e "UPDATE commerce_shipping_method_field_data SET plugin__target_plugin_configuration = '${result}' WHERE plugin__target_plugin_configuration = '${plugin__target_plugin_configuration}';"
done

# Delete conditional showing of the payment method
mysql 'drupal' -u 'root' -p'example' -h 'mysql' -e 'DELETE FROM commerce_shipping_method__conditions;'

php -d memory_limit=-1 "$path_drupal" moi commerce_multisafepay_payments
php "$path_drush" cr

if [ ! -d "$path_image_catalog" ]; then
  mkdir -p "$path_image_catalog"
  find "$path_image_origin" -type f -iname '*.jpg' -exec cp -f {} "$path_image_catalog" \; 2>/dev/null
fi

if [ ! -d "$path_image_medium" ]; then
  mkdir -p "$path_image_medium"
  find "$path_image_origin" -type f -iname '*.jpg' -exec cp -f {} "$path_image_medium" \; 2>/dev/null
fi

docker-php-entrypoint apache2-foreground
