#!/bin/bash

# Setup Apache + PHP-FPM for Laravel on Ubuntu
# Usage: ./setup-apache-phpfpm.sh [EC2_IP]

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

echo -e "${YELLOW}Setting up Apache + PHP-FPM on EC2 ($EC2_IP)...${NC}"
echo ""

# Step 1: Install Apache and PHP
echo -e "${YELLOW}1. Installing Apache and PHP packages...${NC}"
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << 'EOF'
    sudo apt update
    sudo apt install -y apache2 php8.3-fpm php8.3-mbstring php8.3-xml php8.3-mysql libapache2-mod-fcgid php8.3-cli php8.3-common php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath
    echo "✓ Packages installed"
EOF

# Step 2: Enable required Apache modules
echo -e "${YELLOW}2. Enabling Apache modules...${NC}"
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << 'EOF'
    sudo a2enmod rewrite
    sudo a2enmod proxy_fcgi setenvif
    sudo a2enconf php8.3-fpm
    echo "✓ Apache modules enabled"
EOF

# Step 3: Configure PHP-FPM
echo -e "${YELLOW}3. Configuring PHP-FPM...${NC}"
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << 'EOF'
    # Update PHP-FPM to listen on socket
    sudo sed -i 's/listen = .*/listen = \/run\/php\/php8.3-fpm.sock/' /etc/php/8.3/fpm/pool.d/www.conf
    sudo systemctl restart php8.3-fpm
    echo "✓ PHP-FPM configured"
EOF

# Step 4: Create Apache Virtual Host
echo -e "${YELLOW}4. Creating Apache Virtual Host configuration...${NC}"
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << EOF
    sudo tee /etc/apache2/sites-available/scrapmate-admin.conf > /dev/null << 'APACHE_CONFIG'
<VirtualHost *:80>
    ServerName $EC2_IP
    ServerAlias www.scrapmate-admin.local
    
    DocumentRoot $REMOTE_PATH/public
    
    <Directory $REMOTE_PATH/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # PHP-FPM configuration
        <FilesMatch \.php$>
            SetHandler "proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost"
        </FilesMatch>
    </Directory>
    
    # Logging
    ErrorLog \${APACHE_LOG_DIR}/scrapmate-admin-error.log
    CustomLog \${APACHE_LOG_DIR}/scrapmate-admin-access.log combined
    
    # Security headers
    <IfModule mod_headers.c>
        Header set X-Content-Type-Options "nosniff"
        Header set X-Frame-Options "SAMEORIGIN"
    </IfModule>
</VirtualHost>
APACHE_CONFIG
    
    # Enable the site
    sudo a2ensite scrapmate-admin.conf
    sudo a2dissite 000-default.conf 2>/dev/null || true
    echo "✓ Virtual host configured"
EOF

# Step 5: Set proper permissions
echo -e "${YELLOW}5. Setting file permissions...${NC}"
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << EOF
    sudo chown -R www-data:www-data $REMOTE_PATH/storage
    sudo chown -R www-data:www-data $REMOTE_PATH/bootstrap/cache
    sudo chmod -R 775 $REMOTE_PATH/storage
    sudo chmod -R 775 $REMOTE_PATH/bootstrap/cache
    sudo chown -R $REMOTE_USER:www-data $REMOTE_PATH
    echo "✓ Permissions set"
EOF

# Step 6: Test Apache configuration
echo -e "${YELLOW}6. Testing Apache configuration...${NC}"
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << 'EOF'
    if sudo apache2ctl configtest 2>&1 | grep -q "Syntax OK"; then
        echo "✓ Apache configuration is valid"
    else
        echo "✗ Apache configuration has errors"
        sudo apache2ctl configtest
        exit 1
    fi
EOF

# Step 7: Restart services
echo -e "${YELLOW}7. Restarting services...${NC}"
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << 'EOF'
    sudo systemctl restart php8.3-fpm
    sudo systemctl restart apache2
    sudo systemctl enable php8.3-fpm
    sudo systemctl enable apache2
    echo "✓ Services restarted and enabled"
EOF

# Step 8: Verify services are running
echo -e "${YELLOW}8. Verifying services...${NC}"
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << 'EOF'
    if systemctl is-active --quiet apache2; then
        echo "✓ Apache is running"
    else
        echo "✗ Apache is not running"
        exit 1
    fi
    
    if systemctl is-active --quiet php8.3-fpm; then
        echo "✓ PHP-FPM is running"
    else
        echo "✗ PHP-FPM is not running"
        exit 1
    fi
EOF

# Step 9: Update .env APP_URL
echo -e "${YELLOW}9. Updating APP_URL in .env...${NC}"
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
echo -e "${GREEN}=== Apache + PHP-FPM Setup Complete ===${NC}"
echo ""
echo -e "${GREEN}Your Laravel application is now accessible at:${NC}"
echo -e "   http://$EC2_IP"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Make sure your EC2 Security Group allows HTTP (port 80) traffic"
echo "2. Test the application: curl http://$EC2_IP"
echo "3. Check Apache logs if needed: sudo tail -f /var/log/apache2/scrapmate-admin-error.log"
echo "4. Check PHP-FPM status: sudo systemctl status php8.3-fpm"
echo ""
echo -e "${YELLOW}Useful commands:${NC}"
echo "  - Restart Apache: sudo systemctl restart apache2"
echo "  - Restart PHP-FPM: sudo systemctl restart php8.3-fpm"
echo "  - View Apache logs: sudo tail -f /var/log/apache2/scrapmate-admin-error.log"
echo "  - Test config: sudo apache2ctl configtest"



