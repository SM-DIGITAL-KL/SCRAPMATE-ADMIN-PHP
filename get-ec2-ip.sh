#!/bin/bash

# Helper script to get EC2 instance IP address
# Usage: ./get-ec2-ip.sh

export AWS_ACCESS_KEY_ID=AKIASY6OQMSMLTUQAOTS
export AWS_SECRET_ACCESS_KEY=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
export AWS_REGION=ap-south-1

INSTANCE_ID="i-00ca58fa9dc19d34d"

echo "Attempting to get EC2 instance IP address..."
IP=$(aws ec2 describe-instances \
    --instance-ids $INSTANCE_ID \
    --region ap-south-1 \
    --query 'Reservations[0].Instances[0].PublicIpAddress' \
    --output text 2>/dev/null)

if [ -z "$IP" ] || [ "$IP" == "None" ] || [ "$IP" == "null" ]; then
    echo "Could not retrieve IP address automatically."
    echo "You may need to:"
    echo "  1. Check AWS console for the IP address"
    echo "  2. Or provide the IP manually when running deploy.sh"
    exit 1
else
    echo "EC2 Instance IP: $IP"
    echo ""
    echo "You can now run: ./deploy.sh $IP"
fi

