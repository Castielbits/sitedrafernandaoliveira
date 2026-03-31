$ErrorActionPreference = "Stop"
$phpDir = "$PSScriptRoot\.php-local"
$zipPath = "$phpDir\php.zip"
$phpVCRedist = "VCRUNTIME140.dll" # Just a common check file for redist

if (-Not (Test-Path -Path "$phpDir\php.exe")) {
    Write-Host "==== Preparando o Ambiente Local ====" -ForegroundColor Cyan
    Write-Host "Estou baixando o motor do PHP para o seu computador rodar o painel..." -ForegroundColor Yellow
    Write-Host "Isso é feito apenas na primeira vez! Aguarde um instante..." -ForegroundColor Yellow
    
    if (-Not (Test-Path -Path $phpDir)) {
        New-Item -ItemType Directory -Force -Path $phpDir | Out-Null
    }
    
    try {
        Invoke-WebRequest -Uri "https://windows.php.net/downloads/releases/archives/php-8.2.11-nts-Win32-vs16-x64.zip" -OutFile $zipPath
        Write-Host "Download concluído! Extraindo os arquivos..." -ForegroundColor Cyan
        Expand-Archive -Path $zipPath -DestinationPath $phpDir -Force
        Remove-Item $zipPath -Force
    } catch {
        Write-Host "Erro ao baixar ou extrair o PHP. $_" -ForegroundColor Red
        exit
    }
}

Write-Host "==== Tudo Pronto! ====" -ForegroundColor Green
Write-Host "O seu servidor local está rodando." -ForegroundColor Green
Write-Host "Estou abrindo o seu navegador na porta 8000..." -ForegroundColor Cyan
Write-Host "Pressione CTRL+C aqui quando quiser desligar o servidor." -ForegroundColor Yellow

# Abre o site no navegador padrão do Windows (ele vai abrir direto no admin!)
Start-Process "http://localhost:8000/admin.php"

# Roda o servidor na porta 8000 ouvindo essa mesma pasta
& "$phpDir\php.exe" -S localhost:8000
