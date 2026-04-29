@echo off
REM Dayflow Setup Script for Windows

echo.
echo 🚀 Dayflow Setup - Iniciando aplicacao...
echo.

REM Check Docker
docker --version >nul 2>&1
if errorlevel 1 (
    echo ⚠️  Docker nao esta instalado. Instale em: https://www.docker.com/products/docker-desktop
    pause
    exit /b 1
)

echo ✓ Docker esta instalado
echo.

REM Check Docker Compose
docker-compose --version >nul 2>&1
if errorlevel 1 (
    echo ⚠️  Docker Compose nao esta instalado.
    pause
    exit /b 1
)

echo ✓ Docker Compose esta instalado
echo.

REM Start containers
echo ▶ Iniciando containers...
docker-compose up -d

REM Wait for services
echo.
echo ⏳ Aguardando servicos iniciarem...
timeout /t 10 /nobreak

REM Run migrations
echo.
echo ▶ Executando migrations...
docker-compose exec -T backend php artisan migrate:fresh --seed --force

REM Clear cache
echo ▶ Limpando cache...
docker-compose exec -T backend php artisan cache:clear

echo.
echo ✅ Dayflow iniciado com sucesso!
echo.
echo 📍 URLs de acesso:
echo   • Frontend:   http://localhost:3000
echo   • Backend:    http://localhost:8000
echo   • Mailpit:    http://localhost:8025
echo.
echo ⚠️  IMPORTANTE: Configure Google OAuth!
echo   Veja: docs/GOOGLE_OAUTH_SETUP.md
echo.
pause
