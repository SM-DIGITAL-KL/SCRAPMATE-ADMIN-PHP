#!/bin/bash

# Fix UI assets on EC2 server
# Usage: ./fix-ui-assets.sh [EC2_IP]

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Configuration
PEM_FILE="/Users/shijo/Documents/GitHub/SCRAPMATE-ADMIN-PHP/scrapmate-admin.pem"
REMOTE_USER="ubuntu"
REMOTE_PATH="/var/www/scrapmate-admin"
LOCAL_PATH="/Users/shijo/Documents/GitHub/SCRAPMATE-ADMIN-PHP"

# Get EC2 IP
if [ -z "$1" ]; then
    EC2_IP="34.236.152.127"
else
    EC2_IP="$1"
fi

echo -e "${YELLOW}Fixing UI assets on EC2 ($EC2_IP)...${NC}"
echo ""

# 1. Sync all public assets
echo -e "${YELLOW}1. Syncing all public assets...${NC}"
rsync -avz --progress \
    -e "ssh -i $PEM_FILE" \
    --exclude '.DS_Store' \
    "$LOCAL_PATH/public/assets/" \
    "$REMOTE_USER@$EC2_IP:$REMOTE_PATH/public/assets/"

# 2. Update APP_URL
echo -e "${YELLOW}2. Updating APP_URL...${NC}"
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" "cd $REMOTE_PATH && sed -i 's|APP_URL=.*|APP_URL=http://$EC2_IP:8000|' .env && echo 'APP_URL updated'"

# 3. Clear all caches
echo -e "${YELLOW}3. Clearing all caches...${NC}"
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" "cd $REMOTE_PATH && php artisan config:clear && php artisan view:clear && php artisan cache:clear && php artisan route:clear 2>/dev/null || true && echo 'Caches cleared'"

# 4. Set proper permissions
echo -e "${YELLOW}4. Setting proper permissions...${NC}"
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" "sudo chown -R ubuntu:www-data $REMOTE_PATH/public/assets && sudo chmod -R 755 $REMOTE_PATH/public/assets && echo 'Permissions set'"

# 5. Verify critical files
echo -e "${YELLOW}5. Verifying critical assets...${NC}"
CRITICAL_FILES=(
    "public/assets/css/style.css"
    "public/assets/vendor/global/global.min.js"
    "public/assets/js/custom.min.js"
    "public/assets/images/scrap.png"
)

for file in "${CRITICAL_FILES[@]}"; do
    if ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" "test -f $REMOTE_PATH/$file"; then
        echo -e "${GREEN}✓ $file${NC}"
    else
        echo -e "${RED}✗ $file (MISSING)${NC}"
    fi
done

echo ""
echo -e "${GREEN}=== UI Assets Fix Complete ===${NC}"
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Hard refresh your browser (Ctrl+F5 or Cmd+Shift+R)"
echo "2. Clear browser cache if issues persist"
echo "3. Check browser console (F12) for any 404 errors"
echo "4. Verify assets are loading: http://$EC2_IP:8000/assets/css/style.css"

