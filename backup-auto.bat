@echo off
rem Silent daily backup task for TipStop Network (auto mode)
cd /d "%~dp0"

if not exist backups mkdir backups

for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HH-mm"') do set TS=%%i

copy /y "database\database.sqlite" "backups\backup-%TS%.sqlite" >nul
