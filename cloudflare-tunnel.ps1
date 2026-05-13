# CARA MENJALANKAN CLAUDEFLARE TUNNEL DI CMD
# powershell -ExecutionPolicy Bypass -File .\cloudflare-tunnel.ps1
# taskkill /F /IM cloudflared.exe

# cloudflare-tunnel.ps1
param(
    [int]$port = 8000,
    [string]$cloudflaredPath = "D:\Aplikasi\cloudflare\cloudflared.exe"  # <-- sesuaikan jika nama file berbeda (mis. cloudflared tanpa .exe)
)

$logFile = "storage/logs/cloudflared.log"

# 1. Periksa apakah cloudflared tersedia di path yang diberikan
if (-not (Test-Path $cloudflaredPath)) {
    Write-Error "cloudflared tidak ditemukan di: $cloudflaredPath"
    Write-Host "Anda bisa mengunduhnya di: https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/"
    exit 1
}

# 2. Pastikan php artisan serve berjalan di port $port
Write-Host "Memeriksa apakah php artisan serve sudah berjalan di port $port..." -ForegroundColor Cyan
$listening = netstat -ano | Select-String ":$port " | Select-String "LISTENING"
if (-not $listening) {
    Write-Host "php artisan serve belum berjalan. Menjalankannya sekarang..." -ForegroundColor Yellow
    $phpProcess = Start-Process -FilePath "php" -ArgumentList "artisan serve --port=$port" -WindowStyle Hidden -PassThru
    Write-Host "Menunggu server siap..." -ForegroundColor Yellow
    Start-Sleep -Seconds 3   # beri waktu server untuk start
    # Verifikasi ulang
    $retry = 0
    while ($retry -lt 10) {
        $listening = netstat -ano | Select-String ":$port " | Select-String "LISTENING"
        if ($listening) { break }
        Start-Sleep -Seconds 1
        $retry++
    }
    if (-not $listening) {
        Write-Error "Gagal menjalankan php artisan serve di port $port."
        Stop-Process -Id $phpProcess.Id -Force -ErrorAction SilentlyContinue
        exit 1
    }
    Write-Host "Server development Laravel siap di port $port." -ForegroundColor Green
} else {
    Write-Host "Server sudah berjalan di port $port." -ForegroundColor Green
}

# 3. Hentikan proses cloudflared sebelumnya (jika ada)
Write-Host "Membersihkan tunnel sebelumnya..." -ForegroundColor Cyan
Stop-Process -Name "cloudflared" -Force -ErrorAction SilentlyContinue

# 4. Hapus log lama
if (Test-Path $logFile) {
    Remove-Item $logFile -Force
}

# 5. Jalankan cloudflared tunnel
Write-Host "Menjalankan Cloudflare Tunnel untuk localhost:$port..." -ForegroundColor Cyan
$process = Start-Process -FilePath $cloudflaredPath -ArgumentList "tunnel --url http://localhost:$port" -RedirectStandardError $logFile -WindowStyle Hidden -PassThru

Write-Host "Tunnel berjalan (PID: $($process.Id)). Menunggu URL..." -ForegroundColor Yellow

# 6. Tunggu URL muncul di log
$url = $null
$timeout = 30
$timer = 0

while ($null -eq $url -and $timer -lt $timeout) {
    Start-Sleep -Seconds 1
    $timer++
    
    if (Test-Path $logFile) {
        $match = Select-String -Path $logFile -Pattern 'https://[a-zA-Z0-9-]+\.trycloudflare\.com' -ErrorAction SilentlyContinue
        if ($match) {
            $url = $match.Matches[0].Value
        }
    }
}

if ($null -eq $url) {
    Write-Error "Waktu habis (timeout) saat menunggu URL Cloudflare Tunnel. Cek file $logFile untuk melihat error."
    Stop-Process -Id $process.Id -Force -ErrorAction SilentlyContinue
    exit 1
}

Write-Host "`n=======================================================" -ForegroundColor Green
Write-Host "Tunnel URL: $url" -ForegroundColor Green
Write-Host "=======================================================`n" -ForegroundColor Green

# 7. Update file .env
$envFile = ".env"
if (Test-Path $envFile) {
    Write-Host "Mengupdate file .env..." -ForegroundColor Cyan
    $envContent = Get-Content $envFile
    
    # Update APP_URL
    if ($envContent -match '^APP_URL=') {
        $envContent = $envContent -replace '^APP_URL=.*', "APP_URL=$url"
    } else {
        $envContent += "APP_URL=$url"
    }
    
    # Update ASSET_URL
    if ($envContent -match '^ASSET_URL=') {
        $envContent = $envContent -replace '^ASSET_URL=.*', "ASSET_URL=$url"
    } else {
        $envContent += "ASSET_URL=$url"
    }
    
    $envContent | Set-Content $envFile
    Write-Host "File .env berhasil diupdate!" -ForegroundColor Green
    
    # Bersihkan cache Laravel
    Write-Host "Membersihkan cache Laravel..." -ForegroundColor Cyan
    php artisan optimize:clear
}

Write-Host "`nTunnel sedang berjalan di background."
Write-Host "Untuk menghentikan tunnel, jalankan perintah: Stop-Process -Name cloudflared" -ForegroundColor Yellow