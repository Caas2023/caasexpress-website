# Script de reorganização completo (OpenSpec apply)
$ErrorActionPreference = "Stop"

Write-Host "Iniciando reestruturação..." -ForegroundColor Cyan

# 1. Remover api/src duplicada
if (Test-Path ".\api\src") {
    Write-Host "Removendo api/src duplicada..."
    Remove-Item ".\api\src" -Recurse -Force
}

# 2. Separar Controllers (PHP/JS)
Write-Host "Reorganizando Controllers..."
New-Item -ItemType Directory -Force -Path ".\src\controllers\php" | Out-Null
New-Item -ItemType Directory -Force -Path ".\src\controllers\js" | Out-Null

if (Test-Path ".\src\controllers\*.php") { Move-Item -Path ".\src\controllers\*.php" -Destination ".\src\controllers\php\" -Force }
if (Test-Path ".\src\controllers\*.js") { Move-Item -Path ".\src\controllers\*.js" -Destination ".\src\controllers\js\" -Force }

# 3. Criar subpastas em tools/
Write-Host "Criando pastas e movendo scripts de tools/..."
New-Item -ItemType Directory -Force -Path ".\tools\debug" | Out-Null
New-Item -ItemType Directory -Force -Path ".\tools\seo" | Out-Null
New-Item -ItemType Directory -Force -Path ".\tools\scripts" | Out-Null
New-Item -ItemType Directory -Force -Path ".\tools\import" | Out-Null

# Mover scripts da raiz para tools/debug
$debugScripts = @("check_links.php", "check_post.php", "check_real_interlinks.php", "check_seo_data.php", "check_status.php", "debug_links.php", "debug_links_deep.php", "test_links.php", "audit.php", "audit_json.php")
foreach ($script in $debugScripts) {
    if (Test-Path ".\$script") { Move-Item ".\$script" ".\tools\debug\" -Force }
}

# Mover scripts de import
if (Test-Path ".\setup_categories_authors.php") { Move-Item ".\setup_categories_authors.php" ".\tools\import\" -Force }
if (Test-Path ".\n8n-import-wordpress.json") { Move-Item ".\n8n-import-wordpress.json" ".\tools\import\" -Force }

# Mover bat e server_router da raiz
if (Test-Path ".\start_server.bat") { Move-Item ".\start_server.bat" ".\tools\" -Force }
if (Test-Path ".\start_seo_robot.bat") { Move-Item ".\start_seo_robot.bat" ".\tools\" -Force }
if (Test-Path ".\server_router.php") { Move-Item ".\server_router.php" ".\tools\" -Force }

# Mover conteúdo de antigravity-kit para tools/seo
if (Test-Path ".\antigravity-kit") {
    Write-Host "Movendo arquivos de SEO..."
    $seoFiles = Get-ChildItem -Path ".\antigravity-kit" -File
    foreach ($file in $seoFiles) { Move-Item $file.FullName ".\tools\seo\" -Force }

    if (Test-Path ".\antigravity-kit\scripts") {
        $scripts = Get-ChildItem -Path ".\antigravity-kit\scripts" -File
        foreach ($s in $scripts) { Move-Item $s.FullName ".\tools\scripts\" -Force }
    }
}

# 4. Limpar Pastas Vazias/Legacy
Write-Host "Removendo pastas vazias..."
if (Test-Path ".\php") { Remove-Item ".\php" -Recurse -Force }
if (Test-Path ".\antigravity-kit") { Remove-Item ".\antigravity-kit" -Recurse -Force }
if (Test-Path ".\temp_posts.json") { Remove-Item ".\temp_posts.json" -Force }

Write-Host "Reestruturação física concluída com sucesso! 🎉" -ForegroundColor Green
