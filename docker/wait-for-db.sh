#!/bin/bash

# Script para aguardar o banco de dados estar pronto
echo "🔄 Waiting for database to be ready..."

# Aguarda até 60 segundos para o banco estar pronto
for i in {1..60}; do
    if mysqladmin ping -h db -u lestjam -ppassword --silent; then
        echo "✅ Database is ready!"
        break
    fi
    
    if [ $i -eq 60 ]; then
        echo "❌ Database connection timeout after 60 seconds"
        exit 1
    fi
    
    echo "⏳ Waiting for database... ($i/60)"
    sleep 1
done

# Aguarda mais 5 segundos para garantir que está estável
echo "⏳ Additional 5 seconds wait for database stability..."
sleep 5

echo "🚀 Starting queue worker..."
exec php artisan queue:work --queue=message_processing,default --sleep=3 --tries=3 --max-time=3600
