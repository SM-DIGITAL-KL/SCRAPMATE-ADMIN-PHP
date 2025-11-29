#!/bin/bash

# Test script for Admin Panel on EC2
# Usage: ./test-admin-panel.sh [EC2_IP]

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
PORT=8000

# Get EC2 IP
if [ -z "$1" ]; then
    EC2_IP="34.236.152.127"  # Default EC2 IP
else
    EC2_IP="$1"
fi

echo -e "${YELLOW}Testing Admin Panel on EC2 instance ($EC2_IP)...${NC}"
echo ""

# Test 1: Check if server is running
echo -e "${YELLOW}1. Checking if Laravel server is running...${NC}"
if ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" "pgrep -f 'php artisan serve' > /dev/null" 2>/dev/null; then
    echo -e "${GREEN}✓ Server is running${NC}"
else
    echo -e "${RED}✗ Server is not running${NC}"
    echo -e "${YELLOW}   Starting server in background...${NC}"
    ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" "cd $REMOTE_PATH && nohup php artisan serve --host=0.0.0.0 --port=$PORT > /dev/null 2>&1 &"
    sleep 2
    echo -e "${GREEN}✓ Server started${NC}"
fi

# Test 2: Check if port is accessible
echo -e "${YELLOW}2. Testing HTTP connection to port $PORT...${NC}"
if curl -s -o /dev/null -w "%{http_code}" --connect-timeout 5 "http://$EC2_IP:$PORT" | grep -q "200\|301\|302"; then
    echo -e "${GREEN}✓ Port $PORT is accessible${NC}"
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" --connect-timeout 5 "http://$EC2_IP:$PORT")
    echo -e "   HTTP Status: $HTTP_CODE"
else
    echo -e "${RED}✗ Port $PORT is not accessible from outside${NC}"
    echo -e "${YELLOW}   This might be due to:${NC}"
    echo "   - Security group not allowing port $PORT"
    echo "   - Server not bound to 0.0.0.0"
    echo ""
    echo -e "${YELLOW}   Setting up SSH tunnel as alternative...${NC}"
    echo -e "${GREEN}   You can access via: http://localhost:8000${NC}"
    echo ""
    echo -e "${YELLOW}   Run this command in another terminal:${NC}"
    echo "   ssh -i $PEM_FILE -L 8000:localhost:8000 $REMOTE_USER@$EC2_IP"
    echo ""
    echo -e "${YELLOW}   Or restart the server bound to 0.0.0.0:${NC}"
    echo "   ssh -i $PEM_FILE $REMOTE_USER@$EC2_IP 'cd $REMOTE_PATH && php artisan serve --host=0.0.0.0 --port=$PORT'"
    exit 1
fi

# Test 3: Test login page
echo -e "${YELLOW}3. Testing login page...${NC}"
LOGIN_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" --connect-timeout 5 "http://$EC2_IP:$PORT/login")
if [ "$LOGIN_RESPONSE" = "200" ]; then
    echo -e "${GREEN}✓ Login page is accessible${NC}"
    echo -e "${GREEN}   Access at: http://$EC2_IP:$PORT/login${NC}"
else
    echo -e "${YELLOW}   Login page returned: $LOGIN_RESPONSE${NC}"
fi

# Test 4: Test root route
echo -e "${YELLOW}4. Testing root route...${NC}"
ROOT_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" --connect-timeout 5 "http://$EC2_IP:$PORT/")
if [ "$ROOT_RESPONSE" = "200" ] || [ "$ROOT_RESPONSE" = "302" ]; then
    echo -e "${GREEN}✓ Root route is accessible${NC}"
    echo -e "${GREEN}   Access at: http://$EC2_IP:$PORT/${NC}"
else
    echo -e "${YELLOW}   Root route returned: $ROOT_RESPONSE${NC}"
fi

echo ""
echo -e "${GREEN}=== Test Summary ===${NC}"
echo -e "${GREEN}Admin Panel URL: http://$EC2_IP:$PORT${NC}"
echo -e "${GREEN}Login URL: http://$EC2_IP:$PORT/login${NC}"
echo ""
echo -e "${YELLOW}Note: If you can't access from browser, check:${NC}"
echo "  1. EC2 Security Group allows inbound traffic on port $PORT"
echo "  2. Server is running with: php artisan serve --host=0.0.0.0 --port=$PORT"
echo "  3. Or use SSH tunnel: ssh -i $PEM_FILE -L 8000:localhost:8000 $REMOTE_USER@$EC2_IP"

