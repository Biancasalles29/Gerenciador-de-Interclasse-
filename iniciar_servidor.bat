@echo off
title Servidor Interclasse - ETEC
color 0b
echo ========================================================
echo       GERENCIADOR DE INTERCLASSE ESCOLAR
echo ========================================================
echo.

set PHP_BIN="%LOCALAPPDATA%\Programs\php\php.exe"

if exist %PHP_BIN% (
    set "PHP_CMD=%PHP_BIN%"
) else (
    where php >nul 2>nul
    if %errorlevel% equ 0 (
        set "PHP_CMD=php"
    ) else (
        echo [ERRO] PHP nao encontrado no sistema.
        pause
        exit /b 1
    )
)

echo [OK] Iniciando servidor em http://127.0.0.1:8000 ...
echo [OK] Pressione CTRL+C para encerrar o servidor.
echo.

start "" "http://127.0.0.1:8000"

%PHP_CMD% -S 127.0.0.1:8000 -t "%~dp0"
pause
