#!/bin/bash
echo "Our docker file"

echo "Rewrite permalinks..."
wp rewrite structure /%postname%/

echo "Success! Your E2E Test Environment is now ready."
