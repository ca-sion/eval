#!/usr/bin/env bash

# ==============================================================================
# Script de déploiement
# ==============================================================================
# Ce script automatise la mise à jour de l'application sur le serveur SSH
# selon les standards officiels de Laravel et Filament.
# ==============================================================================

set -e

echo "🚀 Démarrage du déploiement..."

# 1. Vérification / Initialisation du fichier .env et de la clé d'application
if [ ! -f .env ]; then
    echo "📄 Fichier .env absent : création depuis .env.example..."
    cp .env.example .env
fi

# 2. Activation du mode maintenance (avec rafraîchissement auto des navigateurs)
echo "🔒 Passage en mode maintenance..."
php artisan down --refresh=15 --retry=60 || true

# 3. Récupération des dernières modifications Git
echo "📥 Récupération du code source..."
git pull origin main

# 4. Installation des dépendances PHP de production
echo "📦 Installation des dépendances Composer..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# 5. Vérification et génération de la clé APP_KEY si absente
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    echo "🔑 Génération de la clé d'application (APP_KEY)..."
    php artisan key:generate --force
fi

# 6. Compilation des assets frontend (Vite & Tailwind v4)
echo "🎨 Compilation des assets Frontend..."
if command -v npm &> /dev/null; then
    npm ci --prefer-offline --no-audit || npm install --no-audit
    npm run build
fi

# 7. Exécution des migrations de base de données
echo "🗄️ Exécution des migrations..."
php artisan migrate --force

# 8. Initialisation du compte administrateur si nécessaire (idempotent)
echo "👤 Vérification du compte administrateur initial..."
php artisan db:seed --class=AdminUserSeeder --force

# 9. Optimisation des caches Laravel & Filament
echo "⚡ Optimisation et mise en cache..."
php artisan optimize:clear
php artisan optimize
php artisan view:cache
php artisan filament:cache-components || true
php artisan icons:cache || true

# 10. Redémarrage des workers de file d'attente (si configurés)
# echo "🔄 Redémarrage des files d'attente (Queue)..."
# php artisan queue:restart || true

# 11. Désactivation du mode maintenance
echo "🔓 Réouverture de l'application..."
php artisan up

echo "✅ Déploiement terminé avec succès !"
