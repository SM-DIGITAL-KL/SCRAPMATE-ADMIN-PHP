#!/bin/bash

# Add NODE_API_KEY to server .env file
# Usage: ./add-node-api-key.sh [EC2_IP]

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Configuration
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PEM_FILE="${PEM_FILE:-${SCRIPT_DIR}/scrapmate-admin.pem}"
INSTANCE_ID="i-00ca58fa9dc19d34d"
AWS_REGION="ap-south-1"
REMOTE_USER="ubuntu"
REMOTE_PATH="/var/www/scrapmate-admin"
NODE_API_KEY="zyubkfzeumeoviaqzcsrvfwdzbiwnlnn"

# Export AWS credentials
export AWS_ACCESS_KEY_ID=AKIASY6OQMSMLTUQAOTS
export AWS_SECRET_ACCESS_KEY=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
export AWS_REGION=$AWS_REGION

# Get EC2 IP address
if [ -z "$1" ]; then
    EC2_IP=$(aws ec2 describe-instances \
        --instance-ids $INSTANCE_ID \
        --region $AWS_REGION \
        --query 'Reservations[0].Instances[0].PublicIpAddress' \
        --output text 2>/dev/null)
    
    if [ -z "$EC2_IP" ] || [ "$EC2_IP" == "None" ] || [ "$EC2_IP" == "null" ]; then
        EC2_IP="13.217.224.15"
        echo -e "${YELLOW}Using fallback EC2 IP: $EC2_IP${NC}"
    else
        echo -e "${GREEN}Retrieved EC2 IP from AWS: $EC2_IP${NC}"
    fi
else
    EC2_IP="$1"
    echo -e "${GREEN}Using provided EC2 IP: $EC2_IP${NC}"
fi

# Verify PEM file exists
if [ ! -f "$PEM_FILE" ]; then
    echo -e "${RED}Error: PEM file not found at $PEM_FILE${NC}"
    exit 1
fi

chmod 400 "$PEM_FILE" 2>/dev/null || true

echo -e "${YELLOW}Adding NODE_API_KEY to server .env file ($EC2_IP)...${NC}"

# Test SSH connection
if ! ssh -i "$PEM_FILE" -o StrictHostKeyChecking=no -o ConnectTimeout=10 "$REMOTE_USER@$EC2_IP" "echo 'Connection successful'" 2>/dev/null; then
    echo -e "${RED}Failed to connect to EC2 instance${NC}"
    exit 1
fi

# Add NODE_API_KEY to .env
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << ADD_KEY_EOF
    set -e
    cd $REMOTE_PATH
    
    # Backup .env if it exists
    if [ -f .env ]; then
        BACKUP_FILE=".env.backup.\$(date +%Y%m%d_%H%M%S)"
        sudo cp .env "\$BACKUP_FILE"
        sudo chown ubuntu:ubuntu "\$BACKUP_FILE"
        echo "✓ Backed up .env to: \$BACKUP_FILE"
    else
        echo "⚠ .env file not found, creating new one..."
        sudo touch .env
        sudo chown ubuntu:ubuntu .env
        sudo chmod 600 .env
    fi
    
    # Check if NODE_API_KEY already exists
    if grep -q "^NODE_API_KEY=" .env 2>/dev/null; then
        echo "Updating existing NODE_API_KEY..."
        sed -i "s|^NODE_API_KEY=.*|NODE_API_KEY=$NODE_API_KEY|" .env
        echo "✓ NODE_API_KEY updated"
    else
        echo "Adding NODE_API_KEY..."
        echo "NODE_API_KEY=$NODE_API_KEY" >> .env
        echo "✓ NODE_API_KEY added"
    fi
    
    # Ensure NODE_API_URL is set correctly
    CORRECT_NODE_API_URL="https://uodttljjzj3nh3e4cjqardxip40btqef.lambda-url.ap-south-1.on.aws/api"
    if grep -q "^NODE_API_URL=" .env 2>/dev/null; then
        CURRENT_URL=\$(grep "^NODE_API_URL=" .env | cut -d'=' -f2)
        if [ "\$CURRENT_URL" != "\$CORRECT_NODE_API_URL" ]; then
            echo "Updating NODE_API_URL..."
            sed -i "s|^NODE_API_URL=.*|NODE_API_URL=\$CORRECT_NODE_API_URL|" .env
            echo "✓ NODE_API_URL updated"
        else
            echo "✓ NODE_API_URL is correct"
        fi
    else
        echo "Adding NODE_API_URL..."
        echo "NODE_API_URL=\$CORRECT_NODE_API_URL" >> .env
        echo "✓ NODE_API_URL added"
    fi
    
    # Ensure APP_KEY exists
    if ! grep -q "^APP_KEY=" .env 2>/dev/null || grep -q "^APP_KEY=$" .env 2>/dev/null; then
        echo "Generating APP_KEY..."
        php artisan key:generate --force 2>/dev/null || true
        echo "✓ APP_KEY generated"
    else
        echo "✓ APP_KEY exists"
    fi
    
    # Set proper permissions
    sudo chown ubuntu:ubuntu .env
    sudo chmod 600 .env
    
    # Clear config cache
    echo "Clearing config cache..."
    sudo rm -f bootstrap/cache/config.php 2>/dev/null || true
    php artisan config:clear 2>/dev/null || true
    php artisan config:cache 2>/dev/null || true
    echo "✓ Config cache cleared and rebuilt"
    
    echo ""
    echo "=== .env File Summary ==="
    echo "NODE_API_KEY: \$(grep '^NODE_API_KEY=' .env | cut -d'=' -f2 | cut -c1-10)..."
    echo "NODE_API_URL: \$(grep '^NODE_API_URL=' .env | cut -d'=' -f2)"
    if grep -q "^APP_KEY=" .env; then
        echo "APP_KEY: \$(grep '^APP_KEY=' .env | cut -d'=' -f2 | cut -c1-20)..."
    fi
ADD_KEY_EOF

echo ""
echo -e "${GREEN}✓ NODE_API_KEY added to server .env file${NC}"
echo -e "${YELLOW}The application should now be able to connect to the Node.js API${NC}"

