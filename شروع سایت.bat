@echo off
title TipStop Network - Server
cd /d "%~dp0"

echo ================================================
echo    TipStop Network - Starting website server...
echo ================================================

:: Stop any server already running on port 8000
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :8000 ^| findstr LISTENING') do taskkill /PID %%a /F >nul 2>&1

:: Start Laravel server minimized
start "TipStop Server" /min cmd /c "%LOCALAPPDATA%\php\php.exe artisan serve"

:: Wait a moment, then open the site in browser
timeout /t 3 /nobreak >nul
start http://127.0.0.1:8000

echo.
echo  Server started and opened in your browser.
echo  (Admin panel: http://127.0.0.1:8000/admin)
echo.
echo  You can close this window - the server keeps running.
timeout /t 6 >nul
