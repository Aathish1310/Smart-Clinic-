@echo off
setlocal
title Smart Clinic - first-time setup
cd /d "%~dp0"
echo ============================================================
echo   Smart Clinic Appointment Management System - setup
echo ============================================================
call scripts\_env.bat || (pause & exit /b 1)
echo Using XAMPP at: %XAMPP%
call scripts\start_db.bat || (pause & exit /b 1)

set "DBPASS="
"%MYSQLEXE%" -u root -e "select 1" >nul 2>&1
if errorlevel 1 (
  set /p DBPASS=Your MySQL root password:
)
if defined DBPASS (set "PW=-p%DBPASS%") else (set "PW=")

echo.
echo Creating database smart_clinic ...
"%MYSQLEXE%" -u root %PW% -e "CREATE DATABASE IF NOT EXISTS smart_clinic CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" || (echo Could not connect to MySQL. Check the password. & pause & exit /b 1)
"%MYSQLEXE%" -u root %PW% smart_clinic < database\schema.sql || (echo Schema import failed. & pause & exit /b 1)

if defined DBPASS (
  > includes\config.local.php echo ^<?php define^('DB_PASS', '%DBPASS%'^);
  echo Saved your database password to includes\config.local.php
)

echo.
set /p DEMO=Load demo data (6 doctors, 44 patients, about 500 appointments)? [Y/n]:
if /i not "%DEMO%"=="n" (
  "%PHPEXE%" scripts\seed.php
)
echo.
echo Setup finished. Run start.bat to open the system.
echo Demo logins (password demo1234): admin@clinic.test, priya.jayasuriya@clinic.test,
echo kavitha.ramanathan@clinic.test, nimal.fernando@clinic.test
pause
