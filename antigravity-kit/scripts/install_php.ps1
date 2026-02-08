# install_php.ps1 - Script para instalar PHP no Windows

$phpVersion = "8.3.6"
$phpDir = "C:\php"
$phpUrl = "https://windows.php.net/downloads/releases/php-$phpVersion-nts-Win32-vs16-x64.zip"
$phpZip = "$env:TEMP\php.zip"

Write-Host "=== Instalador PHP para Windows ===" -ForegroundColor Cyan
Write-Host ""

# Verificar se PHP já está instalado
if (Get-Command php -ErrorAction SilentlyContinue) {
    Write-Host "PHP já está instalado:" -ForegroundColor Green
    php -v
    exit 0
}

Write-Host "Baixando PHP $phpVersion..." -ForegroundColor Yellow

try {
    # Baixar o PHP
    [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
    Invoke-WebRequest -Uri $phpUrl -OutFile $phpZip -UseBasicParsing

    # Criar diretório
    if (!(Test-Path $phpDir)) {
        New-Item -Path $phpDir -ItemType Directory -Force | Out-Null
    }

    # Extrair
    Write-Host "Extraindo para $phpDir..." -ForegroundColor Yellow
    Expand-Archive -Path $phpZip -DestinationPath $phpDir -Force

    # Criar php.ini básico
    Copy-Item "$phpDir\php.ini-development" "$phpDir\php.ini" -Force

    # Habilitar extensões essenciais no php.ini
    $phpIni = Get-Content "$phpDir\php.ini"
    $phpIni = $phpIni -replace ';extension=pdo_sqlite', 'extension=pdo_sqlite'
    $phpIni = $phpIni -replace ';extension=sqlite3', 'extension=sqlite3'
    $phpIni = $phpIni -replace ';extension=mbstring', 'extension=mbstring'
    $phpIni = $phpIni -replace ';extension=openssl', 'extension=openssl'
    $phpIni | Set-Content "$phpDir\php.ini"

    # Adicionar ao PATH (sessão atual)
    $env:Path = "$phpDir;$env:Path"

    # Adicionar ao PATH permanente (usuário)
    $userPath = [Environment]::GetEnvironmentVariable("Path", "User")
    if ($userPath -notlike "*$phpDir*") {
        [Environment]::SetEnvironmentVariable("Path", "$phpDir;$userPath", "User")
        Write-Host "PHP adicionado ao PATH do usuário." -ForegroundColor Green
    }

    # Limpar
    Remove-Item $phpZip -Force -ErrorAction SilentlyContinue

    Write-Host ""
    Write-Host "PHP instalado com sucesso!" -ForegroundColor Green
    & "$phpDir\php.exe" -v

    Write-Host ""
    Write-Host "IMPORTANTE: Reinicie o terminal/PowerShell para usar o comando 'php'." -ForegroundColor Yellow
    Write-Host "Ou execute: $phpDir\php.exe -S localhost:8000 router.php" -ForegroundColor Cyan

} catch {
    Write-Host "Erro ao instalar PHP: $_" -ForegroundColor Red
    Write-Host "Tente baixar manualmente em: https://windows.php.net/download/" -ForegroundColor Yellow
    exit 1
}
