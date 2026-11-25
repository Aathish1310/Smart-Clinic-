@echo off
REM Sends appointment reminders for visits in the next 24 hours.
REM Schedule this file in Windows Task Scheduler to run every hour (see README).
cd /d "%~dp0"
call scripts\_env.bat || exit /b 1
"%PHPEXE%" scripts\send_reminders.php
