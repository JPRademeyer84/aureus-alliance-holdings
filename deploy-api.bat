@echo off
echo Deploying API files to XAMPP...

REM Create directories if they don't exist
if not exist "C:\xampp\htdocs\aureus-angel-alliance\api" mkdir "C:\xampp\htdocs\aureus-angel-alliance\api"
if not exist "C:\xampp\htdocs\aureus-angel-alliance\api\admin" mkdir "C:\xampp\htdocs\aureus-angel-alliance\api\admin"
if not exist "C:\xampp\htdocs\aureus-angel-alliance\api\config" mkdir "C:\xampp\htdocs\aureus-angel-alliance\api\config"
if not exist "C:\xampp\htdocs\aureus-angel-alliance\api\packages" mkdir "C:\xampp\htdocs\aureus-angel-alliance\api\packages"
if not exist "C:\xampp\htdocs\aureus-angel-alliance\api\investments" mkdir "C:\xampp\htdocs\aureus-angel-alliance\api\investments"
if not exist "C:\xampp\htdocs\aureus-angel-alliance\api\wallets" mkdir "C:\xampp\htdocs\aureus-angel-alliance\api\wallets"
if not exist "C:\xampp\htdocs\aureus-angel-alliance\api\users" mkdir "C:\xampp\htdocs\aureus-angel-alliance\api\users"

REM Copy all API files
echo Copying config files...
copy "api\config\*" "C:\xampp\htdocs\aureus-angel-alliance\api\config\" /Y

echo Copying admin files...
copy "api\admin\*" "C:\xampp\htdocs\aureus-angel-alliance\api\admin\" /Y

echo Copying packages files...
copy "api\packages\*" "C:\xampp\htdocs\aureus-angel-alliance\api\packages\" /Y

echo Copying investments files...
copy "api\investments\*" "C:\xampp\htdocs\aureus-angel-alliance\api\investments\" /Y

echo Copying wallets files...
copy "api\wallets\*" "C:\xampp\htdocs\aureus-angel-alliance\api\wallets\" /Y

echo Copying users files...
copy "api\users\*" "C:\xampp\htdocs\aureus-angel-alliance\api\users\" /Y

echo Copying root API files...
copy "api\*.php" "C:\xampp\htdocs\aureus-angel-alliance\api\" /Y

echo.
echo ✅ API deployment complete!
echo 🌐 API available at: http://localhost/aureus-angel-alliance/api/
echo.
pause
