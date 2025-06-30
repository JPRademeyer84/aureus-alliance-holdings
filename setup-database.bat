@echo off
echo ========================================
echo    AUREUS ANGEL ALLIANCE - DATABASE SETUP
echo ========================================
echo.
echo This script will help you set up the MySQL database and API.
echo.
echo REQUIREMENTS:
echo 1. XAMPP must be running (Apache + MySQL)
echo 2. phpMyAdmin should be accessible at http://localhost/phpmyadmin
echo.
echo STEP 1: Copy API to XAMPP
echo ========================================
echo.
echo Copying API files to XAMPP htdocs...

REM Try to find XAMPP installation
set XAMPP_PATH=
if exist "C:\xampp\htdocs" set XAMPP_PATH=C:\xampp\htdocs
if exist "C:\XAMPP\htdocs" set XAMPP_PATH=C:\XAMPP\htdocs
if exist "%PROGRAMFILES%\XAMPP\htdocs" set XAMPP_PATH=%PROGRAMFILES%\XAMPP\htdocs
if exist "%PROGRAMFILES(X86)%\XAMPP\htdocs" set XAMPP_PATH=%PROGRAMFILES(X86)%\XAMPP\htdocs

if "%XAMPP_PATH%"=="" (
    echo ERROR: Could not find XAMPP htdocs folder!
    echo Please manually copy the 'api' folder to your XAMPP htdocs directory.
    pause
    goto :database_setup
)

echo Found XAMPP at: %XAMPP_PATH%
echo Copying API files...

REM Create the project directory in htdocs
if not exist "%XAMPP_PATH%\aureus-angel-alliance" mkdir "%XAMPP_PATH%\aureus-angel-alliance"

REM Copy API folder
xcopy /E /I /Y "api" "%XAMPP_PATH%\aureus-angel-alliance\api\"

echo API files copied successfully!
echo.

:database_setup
echo STEP 2: Database Setup
echo ========================================
echo.
echo 1. Open phpMyAdmin in your browser:
echo    http://localhost/phpmyadmin
echo.
echo 2. Click "Import" tab
echo.
echo 3. Choose file: database/init.sql
echo.
echo 4. Click "Go" to execute the SQL
echo.
echo 5. You should see "aureus_angels" database created
echo.
echo ========================================
echo.
echo After setup, your admin panel will:
echo - Save real data to MySQL database
echo - Update the main website immediately
echo - Persist data between sessions
echo.
echo Default admin credentials:
echo Username: admin
echo Password: Underdog8406155100085@123!@#
echo.
echo ========================================
echo.
pause
echo.
echo Opening phpMyAdmin...
start http://localhost/phpmyadmin
echo.
echo Opening database file location...
start explorer "%cd%\database"
echo.
echo Follow the steps above to import the database!
pause
