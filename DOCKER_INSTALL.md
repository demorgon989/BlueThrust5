#  Docker Installation Guide for BlueThrust5

This comprehensive guide provides step-by-step instructions to install and run BlueThrust5 using Docker on a Linux server (tested on Ubuntu). The setup uses a PHP-FPM container for the application and MariaDB for the database, with your host's NGINX as the web server frontend.

##  Prerequisites

Before starting, ensure you have:

- **Docker & Docker Compose**: Install with `sudo apt update && sudo apt install docker.io docker-compose`
- **NGINX**: Installed on the host with `sudo apt install nginx`
- **Git**: For cloning the repository with `sudo apt install git`
- **Secure Credentials**: Generate strong passwords using `pwgen -s 16 1` (install with `sudo apt install pwgen`)

>  **Security Note**: Replace all placeholders in `compose.yml` with your own secure credentials!

##  Step 1: Clone the Repository

Create a directory and clone the BlueThrust5 repository:

```bash
sudo mkdir -p /opt/bluethrust
cd /opt/bluethrust
git clone https://github.com/RedDragonWebDesign/BlueThrust5 .
```

This places the `src/` folder (application code) in `/opt/bluethrust/src`.

Set initial permissions:

```bash
sudo chown -R 33:33 src
sudo chmod -R 775 src
```

##  Step 2: Create the Dockerfile

Create the `Dockerfile` in `/opt/bluethrust`:

```bash
nano Dockerfile
```

Add the following content:

```dockerfile
# Use official PHP 8.1 FPM image
FROM php:8.1-fpm

# Install PHP extensions required by BlueThrust
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Set working directory inside the container
WORKDIR /var/www/html

# Copy only the src folder into the container
COPY src/ .

# Fix permissions so PHP can read/write
RUN chown -R www-data:www-data /var/www/html
```

Save and exit (`Ctrl+X`, then `Y`, then `Enter`).

##  Step 3: Create docker-compose.yml

Create the `compose.yml` file in `/opt/bluethrust`:

```bash
nano compose.yml
```

Add the following content ( **replace placeholders with secure values**):

```yaml
services:
  php:
    build: .
    container_name: bluethrust-php
    volumes:
      - ./src:/var/www/html:rw  # Mount only the src folder
    ports:
      - "9000:9000"  # PHP-FPM port exposed for host Nginx
    depends_on:
      - db
  db:
    image: mariadb:10.6
    container_name: bluethrust-db
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: [REPLACE_WITH_STRONG_ROOT_PASSWORD]
      MYSQL_DATABASE: bluethrust
      MYSQL_USER: [REPLACE_WITH_DB_USERNAME]
      MYSQL_PASSWORD: [REPLACE_WITH_STRONG_DB_PASSWORD]
    volumes:
      - db_data:/var/lib/mysql
volumes:
  db_data:
```

Save and exit.

##  Step 4: Build and Start Containers

From the `/opt/bluethrust` directory:

```bash
# Build the containers
docker compose build

# Start the services
docker compose up -d

# Verify everything is running
docker compose ps
```

Check logs if needed:

```bash
# PHP container logs
docker compose logs php

# Database container logs
docker compose logs db
```

##  Step 5: Configure Host NGINX

NGINX must proxy to the PHP-FPM container on port 9000. Choose one of the following options:

### Option A: Local Testing (Recommended for Development)

For quick local access without a domain (e.g., `http://YOUR_SERVER_IP:5000`):

```bash
sudo nano /etc/nginx/sites-available/bluethrust
```

Add this configuration:

```nginx
server {
    listen 5000;
    server_name localhost;
    root /opt/bluethrust/src;
    index index.php index.html;
    access_log /var/log/nginx/bluethrust.access.log;
    error_log /var/log/nginx/bluethrust.error.log;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME /var/www/html$fastcgi_script_name;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        try_files $uri =404;
        expires max;
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/bluethrust /etc/nginx/sites-enabled/
sudo nginx -t
sudo nginx -s reload
```

Access your installation at: `http://YOUR_SERVER_IP:5000`

### Option B: Domain with HTTPS (Recommended for Production)

For a domain with SSL:

```bash
sudo nano /etc/nginx/sites-available/bluethrust
```

Add this configuration:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;  # Replace with your domain
    return 301 https://www.yourdomain.com$request_uri;
}

server {
    listen 443 ssl;
    server_name www.yourdomain.com;  # Replace with your domain
    root /opt/bluethrust/src;
    index index.php index.html;
    access_log /var/log/nginx/bluethrust.access.log;
    error_log /var/log/nginx/bluethrust.error.log;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME /var/www/html$fastcgi_script_name;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        try_files $uri =404;
        expires max;
    }

    # SSL Configuration (added after Certbot)
    ssl_certificate /etc/letsencrypt/live/www.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/www.yourdomain.com/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;
}
```

Enable and configure SSL:

```bash
sudo ln -s /etc/nginx/sites-available/bluethrust /etc/nginx/sites-enabled/
sudo nginx -t
sudo nginx -s reload
sudo certbot --nginx -d www.yourdomain.com -d yourdomain.com  # Replace with your domain
```

Access your installation at: `https://www.yourdomain.com`

>  **LAN Restriction**: For local network access only, add `allow 192.168.1.0/24; deny all;` to both server blocks (adjust subnet as needed).

##  Step 6: Run the Installer

1. Visit your BlueThrust5 URL (e.g., `http://YOUR_SERVER_IP:5000` or `https://www.yourdomain.com`)
2. The installation wizard will appear
3. Enter your database details:
   - **Host**: `db`
   - **Database Name**: `bluethrust`
   - **Username**: `[Your DB_USERNAME from compose.yml]`
   - **Password**: `[Your DB_PASSWORD from compose.yml]`
   - **Prefix**: Leave empty
4. Set a strong admin key (generate with: `openssl rand -hex 16`)
5. Create your initial admin user with a strong password
6. If `_config.php` write fails, fix permissions and retry:
   ```bash
   sudo chown -R 33:33 src && sudo chmod -R 775 src
   ```

##  Step 7: Secure Post-Installation

After successful installation:

1. **Delete the installer directory**:
   ```bash
   rm -rf src/installer
   ```

2. **Tighten file permissions**:
   ```bash
   sudo chown 33:33 src/_config.php
   sudo chmod 644 src/_config.php
   sudo chmod -R 755 src
   ```

3. **Test admin login** at `/login.php`

##  Troubleshooting

### Common Issues and Solutions

| Issue | Solution |
|-------|----------|
| **No page loads** | Check NGINX logs: `sudo tail -f /var/log/nginx/bluethrust.error.log`<br>Check PHP logs: `docker compose logs php` |
| **Database connection errors** | Wipe and recreate database volume:<br>`docker compose down && docker volume rm bluethrust_db_data && docker compose up -d` |
| **Permission errors** | Re-run permission commands on `src` directory |
| **Port access issues** | Allow firewall ports:<br>`sudo ufw allow 5000` or `sudo ufw allow "Nginx Full"` |

### Useful Commands

```bash
# View all running containers
docker compose ps

# Stop all services  
docker compose down

# Rebuild containers after changes
docker compose build --no-cache

# View real-time logs
docker compose logs -f php
```

##  Production Recommendations

For production deployments:

-  Use HTTPS with valid SSL certificates
-  Implement regular database backups
-  Monitor container logs
-  Set up log rotation
-  Use strong, unique passwords
-  Keep Docker images updated
-  Configure firewall rules properly

##  Need Help?

If you encounter issues:

1. Check the [troubleshooting section](#-troubleshooting) above
2. Review container logs with `docker compose logs`
3. [Create an issue](https://github.com/RedDragonWebDesign/BlueThrust5/issues) on GitHub

---

**Happy gaming! **
