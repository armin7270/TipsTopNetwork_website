web: bash deploy/start.sh
worker: php artisan queue:work database --sleep=3 --tries=3 --timeout=900
