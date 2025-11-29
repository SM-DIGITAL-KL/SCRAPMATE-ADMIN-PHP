#!/bin/bash

# Debug login issues on EC2
# Usage: ./debug-login.sh [EC2_IP]

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

# Get EC2 IP
if [ -z "$1" ]; then
    EC2_IP="34.236.152.127"
else
    EC2_IP="$1"
fi

echo -e "${YELLOW}Debugging login issues on EC2 ($EC2_IP)...${NC}"
echo ""

# Check APP_KEY
echo -e "${YELLOW}1. Checking APP_KEY...${NC}"
APP_KEY=$(ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" "grep '^APP_KEY=' $REMOTE_PATH/.env | cut -d'=' -f2")
if [ -z "$APP_KEY" ] || [ "$APP_KEY" == "base64:" ]; then
    echo -e "${RED}✗ APP_KEY is missing or empty${NC}"
    echo -e "${YELLOW}   Generating new APP_KEY...${NC}"
    ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" "cd $REMOTE_PATH && php artisan key:generate --force"
    echo -e "${GREEN}✓ APP_KEY generated${NC}"
else
    echo -e "${GREEN}✓ APP_KEY is set${NC}"
fi

# Check NODE_API_URL
echo -e "${YELLOW}2. Checking NODE_API_URL...${NC}"
NODE_API_URL=$(ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" "grep '^NODE_API_URL=' $REMOTE_PATH/.env | cut -d'=' -f2")
if [ -z "$NODE_API_URL" ]; then
    echo -e "${RED}✗ NODE_API_URL is not set${NC}"
else
    echo -e "${GREEN}✓ NODE_API_URL: $NODE_API_URL${NC}"
    # Test if Node.js API is reachable
    echo -e "${YELLOW}   Testing Node.js API connectivity...${NC}"
    WEB_BASE_URL=$(echo "$NODE_API_URL" | sed 's|/api$||')
    LOGIN_URL="$WEB_BASE_URL/dologin"
    if curl -s -o /dev/null -w "%{http_code}" --connect-timeout 5 "$LOGIN_URL" | grep -q "200\|404\|405"; then
        echo -e "${GREEN}   ✓ Node.js API is reachable at $LOGIN_URL${NC}"
    else
        echo -e "${RED}   ✗ Node.js API is not reachable at $LOGIN_URL${NC}"
    fi
fi

# Check jQuery file
echo -e "${YELLOW}3. Checking jQuery file...${NC}"
if ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" "test -f $REMOTE_PATH/public/assets/vendor/global/global.min.js"; then
    echo -e "${GREEN}✓ jQuery file exists${NC}"
    FILE_SIZE=$(ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" "stat -f%z $REMOTE_PATH/public/assets/vendor/global/global.min.js 2>/dev/null || stat -c%s $REMOTE_PATH/public/assets/vendor/global/global.min.js 2>/dev/null || echo '0'")
    echo -e "   File size: $FILE_SIZE bytes"
else
    echo -e "${RED}✗ jQuery file not found${NC}"
    echo -e "${YELLOW}   Expected: $REMOTE_PATH/public/assets/vendor/global/global.min.js${NC}"
fi

# Check Laravel logs for errors
echo -e "${YELLOW}4. Checking recent Laravel errors...${NC}"
RECENT_ERRORS=$(ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" "tail -20 $REMOTE_PATH/storage/logs/laravel.log 2>/dev/null | grep -i 'error\|exception' | tail -5 || echo 'No recent errors'")
if [ "$RECENT_ERRORS" != "No recent errors" ] && [ ! -z "$RECENT_ERRORS" ]; then
    echo -e "${RED}Recent errors found:${NC}"
    echo "$RECENT_ERRORS"
else
    echo -e "${GREEN}✓ No recent errors in logs${NC}"
fi

# Check session configuration
echo -e "${YELLOW}5. Checking session configuration...${NC}"
SESSION_DRIVER=$(ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" "grep '^SESSION_DRIVER=' $REMOTE_PATH/.env | cut -d'=' -f2 || echo 'file'")
echo -e "   SESSION_DRIVER: $SESSION_DRIVER"
if [ "$SESSION_DRIVER" == "file" ]; then
    if ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" "test -w $REMOTE_PATH/storage/framework/sessions"; then
        echo -e "${GREEN}   ✓ Session directory is writable${NC}"
    else
        echo -e "${RED}   ✗ Session directory is not writable${NC}"
        echo -e "${YELLOW}   Fixing permissions...${NC}"
        ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" "sudo chmod -R 775 $REMOTE_PATH/storage/framework/sessions && sudo chown -R www-data:www-data $REMOTE_PATH/storage/framework/sessions 2>/dev/null || sudo chown -R apache:apache $REMOTE_PATH/storage/framework/sessions 2>/dev/null || true"
    fi
fi

# Check routes
echo -e "${YELLOW}6. Checking login route...${NC}"
LOGIN_ROUTE=$(ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" "cd $REMOTE_PATH && php artisan route:list | grep dologin || echo 'Route not found'")
if [ "$LOGIN_ROUTE" != "Route not found" ]; then
    echo -e "${GREEN}✓ Login route exists${NC}"
    echo "   $LOGIN_ROUTE"
else
    echo -e "${RED}✗ Login route not found${NC}"
fi

echo ""
echo -e "${GREEN}=== Debug Summary ===${NC}"
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Open browser console (F12) and check for JavaScript errors"
echo "2. Try logging in and check the Network tab for the AJAX request"
echo "3. Check server logs: ssh -i $PEM_FILE $REMOTE_USER@$EC2_IP 'tail -f $REMOTE_PATH/storage/logs/laravel.log'"
echo "4. Test the login endpoint directly: curl -X POST http://$EC2_IP:8000/dologin"

