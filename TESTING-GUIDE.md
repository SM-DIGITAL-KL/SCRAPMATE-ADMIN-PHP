# Testing the Admin Panel on EC2

## Current Status
- Server is running on EC2 at `44.220.130.44`
- Laravel server is running on port 8000
- Login route: `/` or `/login`

## Method 1: SSH Tunnel (Recommended - Works Immediately)

This method creates a secure tunnel through SSH, so you can access the server as if it's running locally.

### Step 1: Create SSH Tunnel
Open a new terminal and run:
```bash
ssh -i /Users/shijo/Documents/GitHub/SCRAPMATE-ADMIN-PHP/scrapmate-admin.pem \
    -L 8000:localhost:8000 \
    ubuntu@44.220.130.44
```

### Step 2: Keep the tunnel open
Leave this terminal window open (don't close it).

### Step 3: Access Admin Panel
Open your browser and go to:
- **Login Page**: http://localhost:8000/login
- **Root**: http://localhost:8000

---

## Method 2: Direct Access (Requires Security Group Configuration)

### Step 1: Configure EC2 Security Group
1. Go to AWS Console → EC2 → Security Groups
2. Find the security group for instance `i-00ca58fa9dc19d34d`
3. Add inbound rule:
   - Type: Custom TCP
   - Port: 8000
   - Source: Your IP (or 0.0.0.0/0 for testing - not recommended for production)

### Step 2: Restart Server Bound to 0.0.0.0
SSH into the server and restart:
```bash
ssh -i /Users/shijo/Documents/GitHub/SCRAPMATE-ADMIN-PHP/scrapmate-admin.pem ubuntu@44.220.130.44

# Once connected:
cd /var/www/scrapmate-admin
pkill -f 'php artisan serve'  # Stop existing server
php artisan serve --host=0.0.0.0 --port=8000
```

### Step 3: Access Admin Panel
Open your browser and go to:
- **Login Page**: http://44.220.130.44:8000/login
- **Root**: http://44.220.130.44:8000

---

## Method 3: Using the Scripts

### Test the connection:
```bash
./test-admin-panel.sh
```

### Start server properly:
```bash
./start-admin-server.sh
```

---

## Quick Test Commands

### Check if server is running:
```bash
ssh -i scrapmate-admin.pem ubuntu@44.220.130.44 \
    "pgrep -f 'php artisan serve' && echo 'Server is running' || echo 'Server is not running'"
```

### View server logs:
```bash
ssh -i scrapmate-admin.pem ubuntu@44.220.130.44 \
    "tail -f /tmp/laravel-server.log"
```

### Stop the server:
```bash
ssh -i scrapmate-admin.pem ubuntu@44.220.130.44 \
    "pkill -f 'php artisan serve'"
```

---

## Testing the Login

1. Access the login page (using Method 1 or 2 above)
2. The login form should be visible
3. Enter your admin credentials
4. After successful login, you should be redirected to `/admin/dashboard`

---

## Troubleshooting

### Server not accessible?
- Check if server is running: `pgrep -f 'php artisan serve'`
- Check if port 8000 is open in security group
- Try SSH tunnel method (Method 1) - it always works

### Getting 500 errors?
- Check Laravel logs: `tail -f storage/logs/laravel.log`
- Check if `.env` file is configured correctly
- Check if Composer dependencies are installed: `composer install`

### Connection refused?
- Server might be bound to 127.0.0.1 instead of 0.0.0.0
- Restart with: `php artisan serve --host=0.0.0.0 --port=8000`

---

## Production Setup (Recommended)

For production, you should use a proper web server (Apache/Nginx) instead of `php artisan serve`:

1. Install Apache or Nginx
2. Configure virtual host pointing to `/var/www/scrapmate-admin/public`
3. Use port 80 (HTTP) or 443 (HTTPS)
4. Set up SSL certificate for HTTPS

