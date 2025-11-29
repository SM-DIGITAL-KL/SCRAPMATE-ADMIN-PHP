#!/bin/bash

# Setup Nginx + PHP-FPM for Laravel on Ubuntu
# Usage: ./setup-nginx-phpfpm.sh [EC2_IP]

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
EC2_IP="${1:-34.236.152.127}"

echo -e "${YELLOW}Setting up Nginx + PHP-FPM on EC2 ($EC2_IP)...${NC}"
echo ""

# Step 1: Install Nginx and PHP-FPM
echo -e "${YELLOW}1. Installing Nginx and PHP packages...${NC}"
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << 'EOF'
    sudo apt update
    sudo apt install -y nginx php8.3-fpm php8.3-mbstring php8.3-xml php8.3-mysql php8.3-cli php8.3-common php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath
    echo "✓ Packages installed"
EOF

# Step 2: Configure PHP-FPM
echo -e "${YELLOW}2. Configuring PHP-FPM...${NC}"
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << 'EOF'
    # Update PHP-FPM to listen on socket
    sudo sed -i 's/listen = .*/listen = \/run\/php\/php8.3-fpm.sock/' /etc/php/8.3/fpm/pool.d/www.conf
    sudo sed -i 's/;listen.owner = www-data/listen.owner = www-data/' /etc/php/8.3/fpm/pool.d/www.conf
    sudo sed -i 's/;listen.group = www-data/listen.group = www-data/' /etc/php/8.3/fpm/pool.d/www.conf
    sudo sed -i 's/;listen.mode = 0660/listen.mode = 0660/' /etc/php/8.3/fpm/pool.d/www.conf
    sudo systemctl restart php8.3-fpm
    echo "✓ PHP-FPM configured"
EOF

# Step 3: Create Nginx Virtual Host
echo -e "${YELLOW}3. Creating Nginx Virtual Host configuration...${NC}"
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << EOF
    sudo tee /etc/nginx/sites-available/scrapmate-admin > /dev/null << 'NGINX_CONFIG'
server {
    listen 80;
    listen [::]:80;
    server_name $EC2_IP;
    root $REMOTE_PATH/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX_CONFIG
    
    # Enable the site
    sudo ln -sf /etc/nginx/sites-available/scrapmate-admin /etc/nginx/sites-enabled/
    sudo rm -f /etc/nginx/sites-enabled/default
    echo "✓ Virtual host configured"
EOF

# Step 4: Set proper permissions
echo -e "${YELLOW}4. Setting file permissions...${NC}"
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << EOF
    sudo chown -R www-data:www-data $REMOTE_PATH/storage
    sudo chown -R www-data:www-data $REMOTE_PATH/bootstrap/cache
    sudo chmod -R 775 $REMOTE_PATH/storage
    sudo chmod -R 775 $REMOTE_PATH/bootstrap/cache
    sudo chown -R $REMOTE_USER:www-data $REMOTE_PATH
    echo "✓ Permissions set"
EOF

# Step 5: Test Nginx configuration
echo -e "${YELLOW}5. Testing Nginx configuration...${NC}"
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << 'EOF'
    if sudo nginx -t 2>&1 | grep -q "successful"; then
        echo "✓ Nginx configuration is valid"
    else
        echo "✗ Nginx configuration has errors"
        sudo nginx -t
        exit 1
    fi
EOF

# Step 6: Restart services
echo -e "${YELLOW}6. Restarting services...${NC}"
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << 'EOF'
    sudo systemctl restart php8.3-fpm
    sudo systemctl reload nginx
    sudo systemctl enable php8.3-fpm
    sudo systemctl enable nginx
    echo "✓ Services restarted and enabled"
EOF

# Step 7: Verify services are running
echo -e "${YELLOW}7. Verifying services...${NC}"
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << 'EOF'
    if systemctl is-active --quiet nginx; then
        echo "✓ Nginx is running"
    else
        echo "✗ Nginx is not running"
        exit 1
    fi
    
    if systemctl is-active --quiet php8.3-fpm; then
        echo "✓ PHP-FPM is running"
    else
        echo "✗ PHP-FPM is not running"
        exit 1
    fi
EOF

# Step 8: Update .env APP_URL
echo -e "${YELLOW}8. Updating APP_URL in .env...${NC}"
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << EOF
    cd $REMOTE_PATH
    if [ -f .env ]; then
        sed -i 's|APP_URL=.*|APP_URL=http://$EC2_IP|' .env
        php artisan config:clear
        echo "✓ APP_URL updated"
    else
        echo "⚠ .env file not found"
    fi
EOF

echo ""
echo -e "${GREEN}=== Nginx + PHP-FPM Setup Complete ===${NC}"
echo ""
echo -e "${GREEN}Your Laravel application is now accessible at:${NC}"
echo -e "   http://$EC2_IP"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Make sure your EC2 Security Group allows HTTP (port 80) traffic"
echo "2. Test the application: curl http://$EC2_IP"
echo "3. Check Nginx logs if needed: sudo tail -f /var/log/nginx/error.log"
echo "4. Check PHP-FPM status: sudo systemctl status php8.3-fpm"
echo ""
echo -e "${YELLOW}Useful commands:${NC}"
echo "  - Reload Nginx: sudo systemctl reload nginx"
echo "  - Restart Nginx: sudo systemctl restart nginx"
echo "  - Restart PHP-FPM: sudo systemctl restart php8.3-fpm"
echo "  - View Nginx logs: sudo tail -f /var/log/nginx/error.log"
echo "  - Test config: sudo nginx -t"



