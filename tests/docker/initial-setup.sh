#!/bin/bash
echo "Setting up E2E test environment..."

echo "Rewrite permalinks..."
wp rewrite structure /%postname%/

echo "Creating .htaccess with WordPress rewrite rules..."
cat > /var/www/html/.htaccess << 'HTACCESS'
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
HTACCESS
chmod 644 /var/www/html/.htaccess


echo "Dismissing SureForms onboarding wizard..."
wp option patch insert srfm_options onboarding_completed yes 2>/dev/null || wp option patch update srfm_options onboarding_completed yes

echo "Disabling onboarding redirect flag..."
wp option delete __srfm_do_redirect --quiet || true

echo "Success! Your E2E Test Environment is now ready."
