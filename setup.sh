#!/bin/bash

# Dayflow Setup Script - Iniciar ambiente completo

echo "🚀 Dayflow Setup - Iniciando aplicação..."
echo ""

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check Docker
if ! command -v docker &> /dev/null; then
    echo -e "${YELLOW}⚠️  Docker não está instalado. Instale em: https://www.docker.com/products/docker-desktop${NC}"
    exit 1
fi

echo -e "${BLUE}✓ Docker está instalado${NC}"

# Check Docker Compose
if ! command -v docker-compose &> /dev/null; then
    echo -e "${YELLOW}⚠️  Docker Compose não está instalado. Instale em: https://docs.docker.com/compose/install/${NC}"
    exit 1
fi

echo -e "${BLUE}✓ Docker Compose está instalado${NC}"
echo ""

# Start containers
echo -e "${BLUE}▶ Iniciando containers...${NC}"
docker-compose up -d

# Wait for services
echo ""
echo -e "${BLUE}⏳ Aguardando serviços iniciarem...${NC}"
sleep 10

# Check if MySQL is ready
echo -e "${BLUE}▶ Verificando MySQL...${NC}"
docker-compose exec -T mysql mysqladmin ping -u root -proot_secret

# Run migrations
echo ""
echo -e "${BLUE}▶ Executando migrations...${NC}"
docker-compose exec -T backend php artisan migrate:fresh --seed --force

# Clear cache
echo -e "${BLUE}▶ Limpando cache...${NC}"
docker-compose exec -T backend php artisan cache:clear

echo ""
echo -e "${GREEN}✅ Dayflow iniciado com sucesso!${NC}"
echo ""
echo -e "${BLUE}📍 URLs de acesso:${NC}"
echo "  • Frontend:   ${GREEN}http://localhost:5173${NC}"
echo "  • Backend:    ${GREEN}http://localhost:8000${NC}"
echo "  • Mailpit:    ${GREEN}http://localhost:8025${NC}"
echo "  • MySQL:      ${GREEN}localhost:3306${NC}"
echo ""
echo -e "${YELLOW}⚠️  IMPORTANTE: Configure Google OAuth!${NC}"
echo "  Veja: docs/GOOGLE_OAUTH_SETUP.md"
echo ""
echo -e "${BLUE}📝 Comandos úteis:${NC}"
echo "  • Ver logs:       docker-compose logs -f backend"
echo "  • Parar:          docker-compose down"
echo "  • Limpar tudo:    docker-compose down -v"
echo "  • Artisan shell:  docker-compose exec backend php artisan tinker"
echo ""
