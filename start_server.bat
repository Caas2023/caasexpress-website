@echo off
echo Iniciando Servidor Caas Express...
echo.

WHERE php >nul 2>nul
IF %ERRORLEVEL% NEQ 0 (
    echo [ERRO] PHP nao encontrado no PATH.
    echo Por favor, instale o PHP ou adicione ao PATH.
    echo Se voce tem XAMPP, tente adicionar C:\xampp\php ao PATH.
    echo.
    pause
    exit /b
)

echo PHP encontrado. Iniciando servidor em http://localhost:8000
echo Pressione Ctrl+C para parar.
echo.
php -S localhost:8000 router.php
