#!/usr/bin/env bash
# Starts a throwaway GovWeb demo on a fresh Linux box (Amazon Linux 2023, as
# used by Vercel Sandbox): installs PHP and MariaDB, loads the demo data and
# serves the app on port 8080. Not for production: PHP's built-in server is
# single-threaded and the demo accounts use the password "password".
set -euo pipefail

APP_DIR="$(cd "$(dirname "$0")/.." && pwd)"

if command -v dnf >/dev/null; then
  sudo dnf install -y -q php8.3-cli php8.3-pdo php8.3-mysqlnd php8.3-mbstring mariadb1011-server \
    || sudo dnf install -y -q php-cli php-pdo php-mysqlnd php-mbstring mariadb105-server
  sudo mkdir -p /run/mariadb /var/lib/mysql
  sudo chown -R mysql:mysql /run/mariadb /var/lib/mysql
  [ -d /var/lib/mysql/mysql ] || sudo -u mysql mariadb-install-db --datadir=/var/lib/mysql >/dev/null
  sudo -u mysql nohup mariadbd --datadir=/var/lib/mysql --socket=/var/lib/mysql/mysql.sock >/tmp/mariadb.log 2>&1 &
elif command -v apt-get >/dev/null; then
  sudo apt-get update -q && sudo DEBIAN_FRONTEND=noninteractive apt-get install -y -q php-cli php-mysql php-mbstring mariadb-server
  sudo service mariadb start
fi

for i in $(seq 1 30); do sudo mariadb -e 'SELECT 1' >/dev/null 2>&1 && break; sleep 1; done

sudo mariadb <<'SQL'
CREATE USER IF NOT EXISTS 'govweb'@'127.0.0.1' IDENTIFIED BY 'govweb-demo';
CREATE USER IF NOT EXISTS 'govweb'@'localhost' IDENTIFIED BY 'govweb-demo';
GRANT ALL ON government_registration.* TO 'govweb'@'127.0.0.1';
GRANT ALL ON government_registration.* TO 'govweb'@'localhost';
SQL
sudo mariadb < "$APP_DIR/database/schema.sql"
sudo mariadb < "$APP_DIR/database/seed.sql"

cd "$APP_DIR"
DB_USER=govweb DB_PASSWORD=govweb-demo nohup php -S 0.0.0.0:8080 -t "$APP_DIR" >/tmp/php.log 2>&1 &
sleep 1
curl -fsS -o /dev/null -w "GovWeb is running: HTTP %{http_code}\n" http://127.0.0.1:8080/login.php
