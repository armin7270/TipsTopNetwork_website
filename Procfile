web: php artisan storage:link || true && (php artisan app:restore-state deploy-state/state.bin || true) && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT
worker: php artisan queue:work database --sleep=3 --tries=3 --timeout=900
