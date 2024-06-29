#!/bin/bash
set -e

composer install --no-dev --optimize-autoloader --classmap-authoritative

php bin/console doctrine:database:create --if-not-exists

EXISTING_TABLES=$(php bin/console dbal:run-sql "SHOW TABLES;" | grep -oP "(?<=Tables_in_gestion_travaux )\w+" || true)

MIGRATION_FILES=$(ls -1 /var/www/html/migrations/*.php)

apply_migrations=false
for migration_file in $MIGRATION_FILES; do
    table_name=$(grep -oP "(?<=CREATE TABLE )\w+" "$migration_file" | head -n 1)

    if echo "$EXISTING_TABLES" | grep -q "$table_name"; then
        echo "Table '$table_name' déjà existante, migration $migration_file ignorée."
    else
        echo "Exécution de la migration $migration_file"
        if php bin/console doctrine:migrations:execute --up Doctrine\Migrations\\"$(basename "$migration_file" .php)"; then
            apply_migrations=true
        else
            echo "Échec de l'exécution de la migration $migration_file"
        fi
    fi
done

if [ "$apply_migrations" = true ]; then
    echo "Migrations appliquées avec succès"
else
    echo "Toutes les migrations ont été vérifiées et aucune nouvelle table à créer."
fi


# Copier les ficher des fonts
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
