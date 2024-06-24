#!/bin/bash
set -e

composer install --no-dev --optimize-autoloader --classmap-authoritative

php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:schema:update --force --complete
php bin/console doctrine:migrations:migrate --quiet

# Copie les fichers de fonts
if [ ! -f /var/www/html/vendor/setasign/fpdf/font/trebuc.php ]; then
    cp /var/www/html/fontPDF/trebuc.php /var/www/html/vendor/setasign/fpdf/font/
fi
if [ ! -f /var/www/html/vendor/setasign/fpdf/font/trebuc.z ]; then
    cp /var/www/html/fontPDF/trebuc.z /var/www/html/vendor/setasign/fpdf/font/
fi
if [ ! -f /var/www/html/vendor/setasign/fpdf/font/Trebuchet-MS-Bold.php ]; then
    cp /var/www/html/fontPDF/Trebuchet-MS-Bold.php /var/www/html/vendor/setasign/fpdf/font/
fi
if [ ! -f /var/www/html/vendor/setasign/fpdf/font/Trebuchet-MS-Bold.z ]; then
    cp /var/www/html/fontPDF/Trebuchet-MS-Bold.z /var/www/html/vendor/setasign/fpdf/font/
fi
if [ ! -f /var/www/html/vendor/setasign/fpdf/font/Trebuchet-MS-Italic.php ]; then
    cp /var/www/html/fontPDF/Trebuchet-MS-Italic.php /var/www/html/vendor/setasign/fpdf/font/
fi
if [ ! -f /var/www/html/vendor/setasign/fpdf/font/Trebuchet-MS-Italic.z ]; then
    cp /var/www/html/fontPDF/Trebuchet-MS-Italic.z /var/www/html/vendor/setasign/fpdf/font/
fi

exec "$@"
