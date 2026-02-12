@echo off
echo ==========================================
echo    CRM Stages - Joomla + PHP + Docker
echo ==========================================
echo.

echo [1/4] Stopping any existing containers...
docker-compose down 2>nul

echo [2/4] Starting Docker containers...
docker-compose up -d

echo.
echo [3/4] Containers starting...
echo      MySQL needs 30-60 seconds to initialize on first run.
echo.

echo [4/4] Waiting for services...
echo      Please wait about 60 seconds...
timeout /t 60 /nobreak >nul

echo.
echo ==========================================
echo    CRM System Should Be Ready!
echo ==========================================
echo.
echo    CRM Interface:  http://localhost:8080/crm-api.php
echo    Setup Check:    http://localhost:8080/setup-crm.php
echo    phpMyAdmin:     http://localhost:8081
echo.
echo    Database credentials:
echo    - Host: db (or localhost:3306 from host)
echo    - User: root / Password: root_password
echo    - Database: joomla_crm
echo.
echo    To stop: docker-compose down
echo    To reset: docker-compose down -v ^&^& start.bat
echo ==========================================
echo.
echo Opening CRM in browser...
start http://localhost:8080/crm-api.php
pause
