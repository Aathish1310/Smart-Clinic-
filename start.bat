@echo off
setlocal
title Smart Clinic - running
cd /d "%~dp0"
call scripts\_env.bat || (pause & exit /b 1)
call scripts\start_db.bat || (pause & exit /b 1)
echo.
echo Smart Clinic is starting at http://localhost:8080
echo Keep this window open while you use the system. Press Ctrl+C to stop.
start "" "http://localhost:8080"
"%PHPEXE%" -S localhost:8080 -t "%~dp0."
