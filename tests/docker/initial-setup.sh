#!/bin/bash

echo "Our docker file"
echo "Setup Astra theme"
wp theme activate astra

# echo "Activate <your-extension>"
# wp plugin activate your-extension

echo "Rewrite permalinks..."
wp rewrite structure /%postname%/ --hard --quiet

echo "Success! Your E2E Test Environment is now ready."
