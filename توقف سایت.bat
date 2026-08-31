@echo off
title TipStop Network - Stop Server
echo Stopping TipStop Network server...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :8000 ^| findstr LISTENING') do taskkill /PID %%a /F >nul 2>&1
echo Done. Server stopped.
timeout /t 2 >nul
