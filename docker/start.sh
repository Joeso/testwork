#!/bin/bash

echo "=========================================="
echo "   CRM Stages - Joomla + PHP + Docker"
echo "=========================================="
echo ""

# Stop existing containers
echo "[1/4] Stopping any existing containers..."
docker-compose down 2>/dev/null

# Remove old volumes for clean start (optional - comment out to keep data)
# docker-compose down -v 2>/dev/null

# Start containers
echo "[2/4] Starting Docker containers..."
docker-compose up -d

echo ""
echo "[3/4] Containers starting..."
echo "     MySQL needs 30-60 seconds to initialize on first run."
echo ""

# Wait for MySQL
echo "[4/4] Waiting for MySQL to be ready..."
MAX_TRIES=60
COUNT=0

while [ $COUNT -lt $MAX_TRIES ]; do
    if docker exec crm_mysql mysqladmin ping -h 127.0.0.1 -uroot -proot_password --silent 2>/dev/null; then
        echo ""
        echo "✅ MySQL is ready!"
        break
    fi
    COUNT=$((COUNT + 1))
    echo -n "."
    sleep 2
done

if [ $COUNT -eq $MAX_TRIES ]; then
    echo ""
    echo "⚠️  MySQL is still starting. Wait a bit more and try accessing the URLs."
fi

echo ""
echo "=========================================="
echo "   ✅ CRM System Ready!"
echo "=========================================="
echo ""
echo "   🚀 CRM Interface:  http://localhost:8080/crm-api.php"
echo "   ⚙️  Setup Check:    http://localhost:8080/setup-crm.php"
echo "   📊 phpMyAdmin:     http://localhost:8081"
echo ""
echo "   Database credentials:"
echo "   - Host: db (or localhost:3306 from host)"
echo "   - User: root / Password: root_password"
echo "   - Database: joomla_crm"
echo ""
echo "   To stop: docker-compose down"
echo "   To reset: docker-compose down -v && ./start.sh"
echo "=========================================="
