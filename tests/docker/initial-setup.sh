#!/bin/bash
echo "Our docker file"
echo "Install Astra"

wp theme install astra --activate

echo "Rewrite permalinks..."
wp rewrite structure /%postname%/

echo "Success! Your E2E Test Environment is now ready."
