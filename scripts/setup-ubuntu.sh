#!/usr/bin/env bash
set -euo pipefail

echo "== Bulls Media Sheets Importer Ubuntu setup =="

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

echo "Project: ${PROJECT_DIR}"

sudo apt-get update
sudo apt-get install -y \
    ca-certificates \
    curl \
    git \
    gnupg

# Docker Engine
if ! command -v docker >/dev/null 2>&1; then
    echo "Installing Docker Engine..."

    sudo install -m 0755 -d /etc/apt/keyrings

    sudo curl -fsSL \
        https://download.docker.com/linux/ubuntu/gpg \
        -o /etc/apt/keyrings/docker.asc

    sudo chmod a+r /etc/apt/keyrings/docker.asc

    sudo tee /etc/apt/sources.list.d/docker.sources >/dev/null <<EOF
Types: deb
URIs: https://download.docker.com/linux/ubuntu
Suites: $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}")
Components: stable
Architectures: $(dpkg --print-architecture)
Signed-By: /etc/apt/keyrings/docker.asc
EOF

    sudo apt-get update

    sudo apt-get install -y \
        docker-ce \
        docker-ce-cli \
        containerd.io \
        docker-buildx-plugin \
        docker-compose-plugin
fi

sudo systemctl enable --now docker || true

if ! groups "$USER" | grep -q '\bdocker\b'; then
    sudo usermod -aG docker "$USER"

    echo
    echo "Docker group permission was added."
    echo "Log out and log back in, then run this script again."
    exit 0
fi

# DDEV
if ! command -v ddev >/dev/null 2>&1; then
    echo "Installing DDEV..."

    sudo install -m 0755 -d /etc/apt/keyrings

    curl -fsSL https://pkg.ddev.com/apt/gpg.key \
        | gpg --dearmor \
        | sudo tee /etc/apt/keyrings/ddev.gpg >/dev/null

    sudo chmod a+r /etc/apt/keyrings/ddev.gpg

    echo "deb [signed-by=/etc/apt/keyrings/ddev.gpg] https://pkg.ddev.com/apt/ * *" \
        | sudo tee /etc/apt/sources.list.d/ddev.list >/dev/null

    sudo apt-get update
    sudo apt-get install -y ddev
fi

cd "${PROJECT_DIR}"

echo "Starting DDEV..."
ddev start

echo "Installing PHP dependencies..."
ddev composer install

echo "Installing frontend dependencies..."
ddev npm install

if [ ! -f .env ]; then
    echo "Creating .env..."
    cp .env.example .env
fi

echo "Generating Laravel application key..."
ddev artisan key:generate --force

echo "Running migrations..."
ddev artisan migrate --force

echo "Building frontend..."
ddev npm run build

echo "Clearing Laravel caches..."
ddev artisan optimize:clear

echo
echo "Setup finished."
echo
echo "Application:"
echo "  http://bulls-media-sheets-importer.ddev.site/"
echo
echo "Next step:"
echo "  Add Google service-account JSON to:"
echo "  storage/app/private/google/service-account.json"
echo
echo "Then share the Google Sheet with the service-account email."
