@echo off
title TipStop Network - Local Server
cd /d "C:\Users\ARMIN7270\Desktop\project\tipstopnetwork website"
echo ============================================
echo   TipStop Network - starting dev server...
echo   URL: http://127.0.0.1:8000
echo   (Close this window to stop the server)
echo ============================================
"C:\Users\ARMIN7270\AppData\Local\php\php.exe" artisan serve --port=8000
pause