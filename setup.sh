#!/bin/bash
# =============================================================
# Manage Inventory - Docker Setup Script
# =============================================================
# This script automates the initial setup of the project
# using Docker. Run it once after cloning the repository.
#
# Usage: ./setup.sh
# =============================================================

set -e

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${CYAN}"
echo "╔══════════════════════════════════════════════════╗"
echo "║     🐳 Manage Inventory - Docker Setup          ║"
echo "╚══════════════════════════════════════════════════╝"
echo -e "${NC}"

# -------------------------------------------------------
# Step 1: Check Docker is installed and running
# -------------------------------------------------------
echo -e "${YELLOW}[1/7]${NC} Verificando Docker..."
if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker no está instalado. Instálalo desde https://docs.docker.com/get-docker/${NC}"
    exit 1
fi

if ! docker info &> /dev/null; then
    echo -e "${RED}❌ Docker no está corriendo. Inícialo antes de continuar.${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Docker está instalado y corriendo.${NC}"

# -------------------------------------------------------
# Step 2: Create .env from .env.example
# -------------------------------------------------------
echo -e "${YELLOW}[2/7]${NC} Configurando archivo de entorno..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo -e "${GREEN}✅ Archivo .env creado desde .env.example${NC}"
else
    echo -e "${CYAN}ℹ️  Archivo .env ya existe, se conserva el actual.${NC}"
fi

# -------------------------------------------------------
# Step 3: Build and start containers
# -------------------------------------------------------
echo -e "${YELLOW}[3/7]${NC} Construyendo y levantando contenedores..."
docker compose up -d --build

echo -e "${GREEN}✅ Contenedores levantados correctamente.${NC}"

# -------------------------------------------------------
# Step 4: Install PHP dependencies
# -------------------------------------------------------
echo -e "${YELLOW}[4/7]${NC} Instalando dependencias de Composer..."
docker compose exec app composer install

echo -e "${GREEN}✅ Dependencias de PHP instaladas.${NC}"

# -------------------------------------------------------
# Step 5: Install Node dependencies and build assets
# -------------------------------------------------------
echo -e "${YELLOW}[5/7]${NC} Instalando dependencias de Node y compilando assets..."
docker compose exec app npm install
docker compose exec app npm run build

echo -e "${GREEN}✅ Assets compilados correctamente.${NC}"

# -------------------------------------------------------
# Step 6: Generate app key
# -------------------------------------------------------
echo -e "${YELLOW}[6/7]${NC} Generando clave de aplicación..."
docker compose exec app php artisan key:generate

echo -e "${GREEN}✅ Clave de aplicación generada.${NC}"

# -------------------------------------------------------
# Step 7: Run migrations and seeders
# -------------------------------------------------------
echo -e "${YELLOW}[7/7]${NC} Ejecutando migraciones y seeders..."
docker compose exec app php artisan migrate --seed --force
docker compose exec app php artisan migrate --path=database/migrations/triggers
docker compose exec app php artisan migrate --path=database/migrations/alter
docker compose exec app php artisan storage:link

echo -e "${GREEN}✅ Base de datos configurada.${NC}"

# -------------------------------------------------------
# Done!
# -------------------------------------------------------
echo ""
echo -e "${CYAN}"
echo "╔══════════════════════════════════════════════════╗"
echo "║          🎉 ¡Setup completado!                  ║"
echo "╠══════════════════════════════════════════════════╣"
echo "║                                                  ║"
echo "║  🌐 App:   http://localhost:8080                 ║"
echo "║  🗄️  DB:    localhost:3307 (usuario: laravel)     ║"
echo "║                                                  ║"
echo "║  Comandos útiles:                                ║"
echo "║  • docker compose exec app php artisan ...       ║"
echo "║  • docker compose exec app bash                  ║"
echo "║  • docker compose logs -f                        ║"
echo "║  • docker compose down                           ║"
echo "║                                                  ║"
echo "╚══════════════════════════════════════════════════╝"
echo -e "${NC}"
