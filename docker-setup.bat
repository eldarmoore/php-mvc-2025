@echo off
REM Docker Setup Script for Windows

echo Setting up Docker environment...

REM Copy environment file if it doesn't exist
if not exist .env (
    echo Creating .env file from .env.docker.example...
    copy .env.docker.example .env
)

REM Build Docker images
echo Building Docker images...
docker-compose build --no-cache

REM Start containers
echo Starting containers...
docker-compose up -d

REM Wait for containers to be ready
echo Waiting for containers to be ready...
timeout /t 10 /nobreak > nul

REM Install dependencies
echo Installing Composer dependencies...
docker-compose exec -T app composer install

echo.
echo Setup complete!
echo.
echo Application: http://localhost:8080
echo PHPMyAdmin: http://localhost:8081
echo Mailhog: http://localhost:8025
echo.
pause
