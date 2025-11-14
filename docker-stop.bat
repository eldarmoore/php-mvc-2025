@echo off
REM Stop Docker containers

echo Stopping Docker containers...
docker-compose down

echo.
echo Containers stopped!
echo.
pause
