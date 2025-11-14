@echo off
REM Start Docker containers

echo Starting Docker containers...
docker-compose up -d

echo.
echo Containers started!
echo Application: http://localhost:8080
echo PHPMyAdmin: http://localhost:8081
echo.
pause
