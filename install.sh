#!/usr/bin/env bash
set -euo pipefail

REPO_URL="https://github.com/Dartsash/website-dartworld.git"
BRANCH="main"

# как у тебя
WEBROOT="/var/www/site"

red() { echo -e "\033[31m$*\033[0m"; }
grn() { echo -e "\033[32m$*\033[0m"; }
ylw() { echo -e "\033[33m$*\033[0m"; }

need_root() {
  if [[ "${EUID}" -ne 0 ]]; then
    red "Run as root: sudo bash install.sh"
    exit 1
  fi
}

ask_yn() {
  local prompt="$1"
  local ans
  while true; do
    read -rp "${prompt} (y/n): " ans || true
    ans="${ans,,}"
    case "$ans" in
      y|yes) return 0 ;;
      n|no)  return 1 ;;
      *) echo "Type y or n." ;;
    esac
  done
}

detect_php_sock() {
  local sock
  sock="$(ls -1 /run/php/php*-fpm.sock 2>/dev/null | sort -V | tail -n1 || true)"
  echo "$sock"
}

write_nginx_http_only_for_cert() {
  # HTTP-only конфиг (без редиректа) — чтобы certbot webroot смог пройти challenge на домен
  local domain="$1"
  local php_sock="$2"
  local conf="/etc/nginx/sites-available/${domain}.conf"

  cat > "$conf" <<EOF
server {
    listen 80;
    server_name ${domain};

    root ${WEBROOT};
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ \$uri.php?\$query_string;
    }

    location ^~ /db/ { deny all; }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:${php_sock};
    }

    location ~ /\.ht { deny all; }
}

# optional: http www -> https apex (без сертификата для www)
server {
    listen 80;
    server_name www.${domain};
    return 301 https://${domain}\$request_uri;
}
EOF

  ln -sf "$conf" "/etc/nginx/sites-enabled/${domain}.conf"
}

write_nginx_https_apex_only_exact() {
  # ТВОЙ стиль: 80 -> https, 443 ssl только на apex-домен, убираем .php
  local domain="$1"
  local php_sock="$2"
  local conf="/etc/nginx/sites-available/${domain}.conf"

  cat > "$conf" <<EOF
server {
    listen 80;
    server_name ${domain};
    return 301 https://\$host\$request_uri;
}

# optional: http www -> https apex (www без сертификата!)
server {
    listen 80;
    server_name www.${domain};
    return 301 https://${domain}\$request_uri;
}

server {
    listen 443 ssl;
    server_name ${domain};

    root ${WEBROOT};
    index index.php index.html;

    ssl_certificate /etc/letsencrypt/live/${domain}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${domain}/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    location / {
        try_files \$uri \$uri/ \$uri.php?\$query_string;
    }

    location ^~ /db/ { deny all; }

    location ~ \.php\$ {
        if (\$request_uri ~* "^(.+)\.php(\$|\\?)") {
            return 301 \$1\$is_args\$args;
        }

        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:${php_sock};
    }

    location ~ /\.ht { deny all; }
}
EOF

  ln -sf "$conf" "/etc/nginx/sites-enabled/${domain}.conf"
}

main() {
  need_root

  echo "========================================"
  echo "DartWorld Website installer"
  echo "Repo: ${REPO_URL}"
  echo "Webroot: ${WEBROOT}"
  echo "========================================"

  local domain=""
  local install_db="no"
  local install_ssl="no"
  local email=""

  if ask_yn "Do you want to set a domain for the site?"; then
    read -rp "Enter domain (example: dartworld.pro): " domain
    domain="${domain,,}"
    [[ -z "$domain" ]] && { red "Domain is empty."; exit 1; }
    # если ввели www.домен — отрежем www.
    domain="${domain#www.}"

    if ask_yn "Install MariaDB (database server) too?"; then
      install_db="yes"
    fi

    if ask_yn "Setup HTTPS (Let's Encrypt) now? (ONLY for apex domain, without www)"; then
      install_ssl="yes"
      read -rp "Email for Let's Encrypt (required): " email
      [[ -z "$email" ]] && { red "Email is empty."; exit 1; }
    fi
  else
    domain="default-site"
    install_ssl="no"

    if ask_yn "Install MariaDB (database server) too?"; then
      install_db="yes"
    fi
  fi

  echo
  ylw "Plan:"
  echo " - Install: nginx, php-fpm, git, curl"
  [[ "$install_db" == "yes" ]] && echo " - Install: mariadb-server + php-mysql"
  echo " - Deploy repo to: ${WEBROOT}"
  [[ "$install_ssl" == "yes" ]] && echo " - Get SSL for: ${domain} (NO www)"
  echo

  if ! ask_yn "Are you sure you want to install now?"; then
    red "Cancelled."
    exit 0
  fi

  echo
  ylw "[1/7] Installing packages..."
  apt update -y
  apt install -y nginx git curl ca-certificates unzip \
    php-fpm php-cli php-curl php-mbstring php-xml php-zip php-gd

  if [[ "$install_db" == "yes" ]]; then
    apt install -y mariadb-server php-mysql
    systemctl enable --now mariadb
  fi

  systemctl enable --now nginx

  echo
  ylw "[2/7] Deploying website..."
  mkdir -p "$WEBROOT"
  if [[ -d "${WEBROOT}/.git" ]]; then
    ylw "Repo exists → pulling updates..."
    git -C "$WEBROOT" fetch --all
    git -C "$WEBROOT" reset --hard "origin/${BRANCH}"
  else
    rm -rf "${WEBROOT:?}/"*
    git clone --depth 1 --branch "$BRANCH" "$REPO_URL" "$WEBROOT"
  fi
  chown -R www-data:www-data "$WEBROOT"

  echo
  ylw "[3/7] Detecting PHP-FPM socket..."
  local php_sock
  php_sock="$(detect_php_sock)"
  if [[ -z "$php_sock" ]]; then
    red "Could not detect PHP-FPM socket in /run/php/"
    ls -la /run/php/ || true
    systemctl status php*-fpm --no-pager || true
    exit 1
  fi
  echo "Using: $php_sock"

  echo
  ylw "[4/7] Nginx config..."
  rm -f /etc/nginx/sites-enabled/default || true

  if [[ "$domain" == "default-site" ]]; then
    # без домена: HTTP-only catch-all по IP
    cat > "/etc/nginx/sites-available/${domain}.conf" <<EOF
server {
    listen 80;
    server_name _;
    root ${WEBROOT};
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ \$uri.php?\$query_string;
    }

    location ^~ /db/ { deny all; }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:${php_sock};
    }

    location ~ /\.ht { deny all; }
}
EOF
    ln -sf "/etc/nginx/sites-available/${domain}.conf" "/etc/nginx/sites-enabled/${domain}.conf"
  else
    # домен: для SSL делаем сначала HTTP-only (без редиректа), чтобы certbot прошёл
    write_nginx_http_only_for_cert "$domain" "$php_sock"
  fi

  nginx -t
  systemctl reload nginx

  echo
  ylw "[5/7] Firewall note (GCP):"
  echo "Enable 'Allow HTTP traffic' (port 80). If SSL: enable 'Allow HTTPS traffic' (port 443)."

  if [[ "$install_ssl" == "yes" ]]; then
    echo
    ylw "[6/7] Getting SSL certificate (ONLY apex domain, NO www)..."
    apt install -y certbot

    certbot certonly --webroot -w "$WEBROOT" \
      -d "$domain" \
      --non-interactive --agree-tos -m "$email" || {
        red "Certbot failed. Обычно: DNS домена не указывает на VM или закрыт порт 80."
        exit 1
      }

    # после сертификата ставим твой final-конфиг (443 только для apex)
    write_nginx_https_apex_only_exact "$domain" "$php_sock"
    nginx -t
    systemctl reload nginx

    systemctl enable --now certbot.timer >/dev/null 2>&1 || true
  fi

  echo
  grn "[7/7] Done!"
  if [[ "$domain" != "default-site" ]]; then
    if [[ "$install_ssl" == "yes" ]]; then
      echo "Open: https://${domain}"
      echo "Note: https://www.${domain} will NOT work (no cert). http://www.${domain} redirects to https://${domain}"
    else
      echo "Open: http://${domain}"
    fi
  else
    echo "Open your VM External IP via HTTP."
  fi
  echo "Webroot: ${WEBROOT}"
}

main "$@"
