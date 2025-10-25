#!/bin/bash

# OptimaSphere ERP - Production Deployment Script
# This script builds assets for production when npm is not available on the server

echo "========================================="
echo "OptimaSphere ERP - Production Build"
echo "========================================="

# Step 1: Install npm dependencies
echo ""
echo "Step 1: Installing npm dependencies..."
npm install

# Step 2: Build assets for production
echo ""
echo "Step 2: Building assets for production..."
npm run build

# Step 3: Create deployment package
echo ""
echo "Step 3: Creating deployment package..."

# Check if public/build directory exists
if [ ! -d "public/build" ]; then
    echo "ERROR: public/build directory not found!"
    echo "Build may have failed. Please check for errors above."
    exit 1
fi

echo ""
echo "========================================="
echo "Build completed successfully!"
echo "========================================="
echo ""
echo "Files created in public/build/:"
ls -lh public/build/
echo ""
echo "DEPLOYMENT INSTRUCTIONS:"
echo "1. Upload the entire 'public/build' directory to your production server"
echo "2. Make sure the path on production is: /home/txoyxssz/optimasphere.webtech-solutions.hu/public/build/"
echo "3. Run migrations on production: php artisan migrate --force"
echo "4. Clear caches: php artisan config:clear && php artisan cache:clear && php artisan view:clear"
echo ""
