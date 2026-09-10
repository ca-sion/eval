#!/usr/bin/env bash

# ==============================================================================
# Script de déploiement
# ==============================================================================
# Ce script automatise la mise à jour de l'application sur le serveur SSH
# selon les standards officiels de Laravel et Filament.
# ==============================================================================

set -e

echo "🚀 Démarrage du déploiement..."

# 1. Activation du mode maintenance (avec rafraîchissement auto des navigateurs)
echo "🔒 Passage en mode maintenance..."
php artisan down --refresh=15 --retry=60 || true

# 2. Récupération des dernières modifications Git
echo "📥 Récupération du code source..."
git pull origin main

# 3. Installation des dépendances PHP de production
echo "📦 Installation des dépendances Composer..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# 4. Compilation des assets frontend (Vite & Tailwind v4)
echo "🎨 Compilation des assets Frontend..."
if command -v npm &> /dev/null; then
    npm ci --prefer-offline --no-audit || npm install --no-audit
    npm run build
fi

# 5. Exécution des migrations de base de données
echo "🗄️ Exécution des migrations..."
php artisan migrate --force

# 6. Initialisation du compte administrateur si nécessaire (idempotent)
echo "👤 Vérification du compte administrateur initial..."
php artisan db:seed --class=AdminUserSeeder --force

# 7. Optimisation des caches Laravel & Filament
echo "⚡ Optimisation et mise en cache..."
php artisan optimize:clear
php artisan optimize
php artisan view:cache
php artisan filament:cache-components || true
php artisan icons:cache || true

# 8. Redémarrage des workers de file d'attente (si configurés)
# echo "🔄 Redémarrage des files d'attente (Queue)..."
# php artisan queue:restart || true

# 9. Désactivation du mode maintenance
echo "🔓 Réouverture de l'application..."
php artisan up

echo "✅ Déploiement terminé avec succès !"
