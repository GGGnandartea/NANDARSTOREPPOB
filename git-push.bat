@echo off
setlocal EnableDelayedExpansion
title Upload Otomatis ke GitHub - NANDARSTORE
color 0A

rem Pastikan folder Git masuk ke PATH sesi ini
set "PATH=%PATH%;C:\Program Files\Git\cmd;C:\Program Files\Git\bin;C:\Users\%USERNAME%\AppData\Local\Programs\Git\cmd"

echo ========================================================
echo       NANDARSTORE2.0 - UPLOAD OTOMATIS KE GITHUB
echo ========================================================
echo.
echo Memeriksa sistem Git...

where git.exe >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] Git tidak ditemukan di C:\Program Files\Git\cmd.
    echo.
    pause
    exit /b
)

echo [OK] Git terdeteksi:
git --version
echo.
echo Memeriksa proteksi file (.gitignore aktif)...
echo File .env, database sqlite, logs, & kunci rahasia TIDAK AKAN diupload.
echo.

if not exist ".git" (
    echo [INFO] Inisialisasi Git Repository lokal...
    git init
    git branch -M main
    echo.
    echo Menghubungkan ke GitHub: https://github.com/GGGnandartea/NANDARSTOREPPOB.git
    git remote add origin https://github.com/GGGnandartea/NANDARSTOREPPOB.git
)

echo [1/3] Memilih file aman yang akan diunggah...
git add .

echo.
set /p commit_msg="Masukkan catatan update (tekan Enter untuk default): "
if "%commit_msg%"=="" set commit_msg=Update toko online NandarStore: %date% %time%

echo.
echo [2/3] Menyimpan perubahan (commit)...
git commit -m "%commit_msg%"

echo.
echo [3/3] Mengunggah ke GitHub (push)...
git push -u origin main

if %errorlevel% neq 0 (
    echo.
    echo --------------------------------------------------------
    echo PERHATIAN:
    echo Jika muncul jendela login GitHub, silakan pilih 'Sign in with browser'.
    echo --------------------------------------------------------
) else (
    echo.
    echo ========================================================
    echo  SUKSES! Semua file aman berhasil diunggah ke GitHub.
    echo ========================================================
)

echo.
pause
