#!/bin/bash

###############################################################################
# Chibank Deployment Script
# This script prepares the application for production deployment
###############################################################################

set -e  # Exit on error

echo "================================================"
echo "  Chibank Production Deployment Preparation"
echo "================================================"
echo ""

# Color codes for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if running as root
if [ "$EUID" -eq 0 ]; then
    echo -e "${RED}Error: Do not run this script as root${NC}"
    exit 1
fi

echo -e "${YELLOW}Step 1: Checking prerequisites...${NC}"

# Check for required commands
command -v php >/dev/null 2>&1 || { echo -e "${RED}PHP is required but not installed.${NC}" >&2; exit 1; }
command -v composer >/dev/null 2>&1 || { echo -e "${RED}Composer is required but not installed.${NC}" >&2; exit 1; }
command -v node >/dev/null 2>&1 || { echo -e "${RED}Node.js is required but not installed.${NC}" >&2; exit 1; }
command -v npm >/dev/null 2>&1 || { echo -e "${RED}npm is required but not installed.${NC}" >&2; exit 1; }

echo -e "${GREEN}✓ All prerequisites met${NC}"
echo ""

echo -e "${YELLOW}Step 2: Installing PHP dependencies...${NC}"
composer install --no-dev --optimize-autoloader --ignore-platform-reqs
echo -e "${GREEN}✓ PHP dependencies installed${NC}"
echo ""

echo -e "${YELLOW}Step 3: Installing Node.js dependencies...${NC}"
npm install --production
echo -e "${GREEN}✓ Node.js dependencies installed${NC}"
echo ""

echo -e "${YELLOW}Step 4: Building frontend assets...${NC}"
npm run build
echo -e "${GREEN}✓ Frontend assets built${NC}"
echo ""

echo -e "${YELLOW}Step 5: Optimizing application...${NC}"

# Check if .env exists
if [ ! -f .env ]; then
    echo -e "${YELLOW}Warning: .env file not found. Copying from .env.example${NC}"
    cp .env.example .env
    echo -e "${YELLOW}Please edit .env with your production settings before deploying!${NC}"
fi

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer dump-autoload --optimize

echo -e "${GREEN}✓ Application optimized${NC}"
echo ""

echo -e "${YELLOW}Step 6: Setting proper permissions...${NC}"
chmod -R 755 storage bootstrap/cache
echo -e "${GREEN}✓ Permissions set${NC}"
echo ""

echo -e "${YELLOW}Step 7: Creating deployment package...${NC}"

# Create deployment directory
DEPLOY_DIR="chibank-deploy-$(date +%Y%m%d-%H%M%S)"
mkdir -p "../$DEPLOY_DIR"

# Copy necessary files and directories
echo "Copying files..."
rsync -av --progress \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='.env' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='tests' \
    --exclude='.phpunit.result.cache' \
    --exclude='*.log' \
    . "../$DEPLOY_DIR/"

# Create necessary directories in deployment package
mkdir -p "../$DEPLOY_DIR/storage/logs"
mkdir -p "../$DEPLOY_DIR/storage/framework/cache"
mkdir -p "../$DEPLOY_DIR/storage/framework/sessions"
mkdir -p "../$DEPLOY_DIR/storage/framework/views"

# Copy .env.example for reference
cp .env.example "../$DEPLOY_DIR/.env.example"

echo -e "${GREEN}✓ Deployment package created${NC}"
echo ""

echo -e "${YELLOW}Step 8: Creating deployment archive...${NC}"
cd ..
tar -czf "${DEPLOY_DIR}.tar.gz" "$DEPLOY_DIR"
rm -rf "$DEPLOY_DIR"
echo -e "${GREEN}✓ Archive created: ${DEPLOY_DIR}.tar.gz${NC}"
echo ""

echo "================================================"
echo -e "${GREEN}Deployment package ready!${NC}"
echo "================================================"
echo ""
echo "Archive location: ../${DEPLOY_DIR}.tar.gz"
echo ""
echo "Next steps:"
echo "1. Upload ${DEPLOY_DIR}.tar.gz to your production server"
echo "2. Extract: tar -xzf ${DEPLOY_DIR}.tar.gz"
echo "3. Configure .env file with production settings"
echo "4. Run migrations: php artisan migrate --force"
echo "5. Set permissions: chown -R www-data:www-data /path/to/chibank"
echo "6. Configure web server (see DEPLOYMENT_GUIDE.md)"
echo ""
echo "For detailed deployment instructions, see DEPLOYMENT_GUIDE.md"
echo ""
