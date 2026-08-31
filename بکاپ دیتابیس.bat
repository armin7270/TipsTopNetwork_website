@echo off
title TipStop Network - Database Backup
cd /d "%~dp0"

if not exist backups mkdir backups

:: Reliable timestamp via PowerShell
for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HH-mm"') do set TS=%%i

copy /y "database\database.sqlite" "backups\backup-%TS%.sqlite" >nul

echo.
echo  Backup created: backups\backup-%TS%.sqlite
echo.

:: In scheduled (auto) mode, do not wait for a key press
if /i not "%~1"=="auto" pause
