#!/bin/bash

# Reload Nginx after code changes
# Usage: ./reload-nginx.sh [EC2_IP]

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration
PEM_FILE="/Users/shijo/Documents/GitHub/SCRAPMATE-ADMIN-PHP/scrapmate-admin.pem"
REMOTE_USER="ubuntu"
REMOTE_PATH="/var/www/scrapmate-admin"
EC2_IP="${1:-34.236.152.127}"

echo -e "${YELLOW}Reloading Nginx on EC2 ($EC2_IP)...${NC}"

# Clear Laravel caches
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << EOF
    cd $REMOTE_PATH
    php artisan config:clear
    php artisan view:clear
    php artisan cache:clear
    echo "✓ Laravel caches cleared"
EOF

# Reload Nginx
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << 'EOF'
    if command -v nginx &> /dev/null; then
        sudo nginx -t && sudo systemctl reload nginx
        echo "✓ Nginx reloaded successfully"
    else
        echo "⚠ Nginx not found - skipping reload"
    fi
EOF

echo -e "${GREEN}✓ Nginx reload complete${NC}"



