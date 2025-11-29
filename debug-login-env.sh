#!/bin/bash

# Debug login and env.txt issues on server
# Usage: ./debug-login-env.sh [EC2_IP]

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

echo -e "${YELLOW}Debugging login and env.txt on EC2 server ($EC2_IP)...${NC}"

# Test SSH connection
if ! ssh -i "$PEM_FILE" -o StrictHostKeyChecking=no -o ConnectTimeout=10 "$REMOTE_USER@$EC2_IP" "echo 'Connection successful'" 2>/dev/null; then
    echo -e "${RED}Failed to connect to EC2 instance${NC}"
    exit 1
fi

# Debug on server
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << DEBUG_EOF
    set -e
    cd $REMOTE_PATH
    
    echo "=== Checking env.txt and .env Files ==="
    echo ""
    echo "env.txt exists: \$(test -f env.txt && echo 'Yes' || echo 'No')"
    if [ -f env.txt ]; then
        echo "env.txt size: \$(stat -c%s env.txt) bytes"
        echo "env.txt permissions: \$(stat -c '%U:%G %a' env.txt)"
        echo ""
        echo "NODE_API_URL from env.txt:"
        grep "^NODE_API_URL=" env.txt || echo "  NOT FOUND"
        echo ""
        echo "NODE_API_KEY from env.txt:"
        grep "^NODE_API_KEY=" env.txt | cut -c1-30 || echo "  NOT FOUND"
    fi
    
    echo ""
    echo ".env exists: \$(test -f .env && echo 'Yes' || echo 'No')"
    if [ -f .env ]; then
        echo ".env size: \$(stat -c%s .env) bytes"
        echo ".env permissions: \$(stat -c '%U:%G %a' .env)"
        echo ""
        echo "NODE_API_URL from .env:"
        grep "^NODE_API_URL=" .env || echo "  NOT FOUND"
        echo ""
        echo "NODE_API_KEY from .env:"
        grep "^NODE_API_KEY=" .env | cut -c1-30 || echo "  NOT FOUND"
    fi
    
    echo ""
    echo "=== Testing EnvReader PHP Class ==="
    php -r "
    require 'vendor/autoload.php';
    \$app = require_once 'bootstrap/app.php';
    \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    use App\Helpers\EnvReader;
    
    echo 'EnvReader::get(\"NODE_API_URL\"): ' . EnvReader::get('NODE_API_URL', 'NOT_FOUND') . PHP_EOL;
    echo 'EnvReader::get(\"NODE_API_KEY\"): ' . substr(EnvReader::get('NODE_API_KEY', 'NOT_FOUND'), 0, 10) . '...' . PHP_EOL;
    echo 'env(\"NODE_API_URL\"): ' . env('NODE_API_URL', 'NOT_FOUND') . PHP_EOL;
    " 2>&1 || echo "⚠ Could not test EnvReader"
    
    echo ""
    echo "=== Recent Login Attempts from Laravel Log ==="
    if [ -f storage/logs/laravel.log ]; then
        sudo tail -n 50 storage/logs/laravel.log | grep -A 5 -B 5 "Login API" | tail -n 30 || echo "No login attempts found in log"
    else
        echo "Laravel log file not found"
    fi
    
    echo ""
    echo "=== Testing API Connection ==="
    CORRECT_URL="https://uodttljjzj3nh3e4cjqardxip40btqef.lambda-url.ap-south-1.on.aws"
    LOGIN_URL="\$CORRECT_URL/dologin"
    API_KEY="zyubkfzeumeoviaqzcsrvfwdzbiwnlnn"
    
    echo "Testing login URL: \$LOGIN_URL"
    HTTP_CODE=\$(curl -s -o /tmp/login_test_response.txt -w "%{http_code}" --max-time 10 \\
        -H "api-key: \$API_KEY" \\
        -H "Accept: application/json" \\
        -H "Content-Type: application/json" \\
        -X POST \\
        -d '{"email":"test@test.com","password":"test"}' \\
        "\$LOGIN_URL" 2>&1 || echo "000")
    
    echo "HTTP Status Code: \$HTTP_CODE"
    if [ -f /tmp/login_test_response.txt ]; then
        echo "Response body:"
        cat /tmp/login_test_response.txt | head -c 500
        echo ""
        rm -f /tmp/login_test_response.txt
    fi
    
    echo ""
    echo "=== Cached Config Check ==="
    if [ -f bootstrap/cache/config.php ]; then
        echo "Config cache exists"
        if grep -q "uodttljjzj3nh3e4cjqardxip40btqef" bootstrap/cache/config.php 2>/dev/null; then
            echo "✓ Cached config contains correct Lambda URL"
        elif grep -q "localhost:3000" bootstrap/cache/config.php 2>/dev/null; then
            echo "⚠ Cached config contains localhost:3000 (WRONG!)"
        else
            echo "⚠ Could not find URL in cached config"
        fi
    else
        echo "Config cache does not exist"
    fi
DEBUG_EOF

echo ""
echo -e "${GREEN}✓ Debug information collected${NC}"

