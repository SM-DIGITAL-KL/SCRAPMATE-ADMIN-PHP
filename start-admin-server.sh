#!/bin/bash

# Start Admin Panel server on EC2
# Usage: ./start-admin-server.sh [EC2_IP]

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
    EC2_IP="34.236.152.127"
else
    EC2_IP="$1"
fi

echo -e "${YELLOW}Starting Admin Panel server on EC2 ($EC2_IP)...${NC}"

# Stop any existing server
echo -e "${YELLOW}Stopping any existing server...${NC}"
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" "pkill -f 'php artisan serve' || true"
sleep 1

# Start server bound to 0.0.0.0 (accessible from outside)
echo -e "${YELLOW}Starting server on 0.0.0.0:$PORT...${NC}"
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" "cd $REMOTE_PATH && nohup php artisan serve --host=0.0.0.0 --port=$PORT > /tmp/laravel-server.log 2>&1 &"
sleep 2

# Verify server is running
if ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" "pgrep -f 'php artisan serve' > /dev/null" 2>/dev/null; then
    echo -e "${GREEN}✓ Server started successfully${NC}"
    echo ""
    echo -e "${GREEN}Admin Panel URLs:${NC}"
    echo -e "   Main: http://$EC2_IP:$PORT"
    echo -e "   Login: http://$EC2_IP:$PORT/login"
    echo ""
    echo -e "${YELLOW}Important:${NC}"
    echo "  1. Make sure EC2 Security Group allows inbound traffic on port $PORT"
    echo "  2. To view server logs: ssh -i $PEM_FILE $REMOTE_USER@$EC2_IP 'tail -f /tmp/laravel-server.log'"
    echo "  3. To stop server: ssh -i $PEM_FILE $REMOTE_USER@$EC2_IP 'pkill -f \"php artisan serve\"'"
    echo ""
    echo -e "${YELLOW}Testing connection...${NC}"
    sleep 1
    if curl -s -o /dev/null -w "%{http_code}" --connect-timeout 5 "http://$EC2_IP:$PORT" | grep -q "200\|301\|302"; then
        echo -e "${GREEN}✓ Server is accessible!${NC}"
    else
        echo -e "${RED}✗ Server not accessible from outside${NC}"
        echo -e "${YELLOW}   Check EC2 Security Group settings${NC}"
        echo -e "${YELLOW}   Or use SSH tunnel:${NC}"
        echo "   ssh -i $PEM_FILE -L 8000:localhost:8000 $REMOTE_USER@$EC2_IP"
        echo "   Then access: http://localhost:8000"
    fi
else
    echo -e "${RED}✗ Failed to start server${NC}"
    echo -e "${YELLOW}Check logs:${NC}"
    ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" "cat /tmp/laravel-server.log" 2>/dev/null || true
fi

