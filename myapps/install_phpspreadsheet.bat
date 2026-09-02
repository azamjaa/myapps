@echo off
echo ========================================
echo Install PhpSpreadsheet untuk MyApps
echo ========================================
echo.

cd /d "%~dp0"
echo Current directory: %CD%
echo.

echo Checking for Composer...
where composer >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo Composer found in PATH
    composer install --no-interaction
) else (
    echo Composer not found in PATH
    echo.
    echo Trying Laragon Composer...
    if exist "d:\laragon\bin\composer\composer.bat" (
        echo Found Laragon Composer
        call "d:\laragon\bin\composer\composer.bat" install --no-interaction
    ) else (
        echo.
        echo ERROR: Composer tidak ditemui!
        echo.
        echo Sila install Composer terlebih dahulu:
        echo 1. Download dari: https://getcomposer.org/Composer-Setup.exe
        echo 2. Install dan pilih "Add to PATH"
        echo 3. Restart terminal ini
        echo 4. Jalankan script ini sekali lagi
        echo.
        pause
        exit /b 1
    )
)

echo.
if exist "vendor\autoload.php" (
    echo ========================================
    echo SUCCESS! PhpSpreadsheet installed!
    echo ========================================
    echo.
    echo Folder vendor/ telah dicipta
    echo File vendor/autoload.php telah dicipta
    echo.
    echo Sila refresh halaman pengurusan_rekod_dashboard.php
    echo.
) else (
    echo ========================================
    echo WARNING: Installation mungkin tidak berjaya
    echo ========================================
    echo.
    echo Sila check error messages di atas
    echo.
)

pause
