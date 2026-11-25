@echo off
setlocal
cd /d "%~dp0"
call scripts\_env.bat || (pause & exit /b 1)
if not exist backups mkdir backups
set "STAMP=%date:~-4%%date:~4,2%%date:~7,2%_%time:~0,2%%time:~3,2%"
set "STAMP=%STAMP: =0%"
"%XAMPP%\mysql\bin\mysqldump.exe" -u root smart_clinic > "backups\smart_clinic_%STAMP%.sql"
if errorlevel 1 (echo Backup failed - if MySQL has a password use: mysqldump -u root -p smart_clinic ^> file.sql) else (echo Backup saved in the backups folder.)
pause
