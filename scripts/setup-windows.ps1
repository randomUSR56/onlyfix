<#
.SYNOPSIS
    OnlyFix Windows Setup Script
.DESCRIPTION
    Sets up hosts file and Docker environment for OnlyFix project on Windows
#>

$ErrorActionPreference = "Stop"

# Colors
function Write-Success { Write-Host $args -ForegroundColor Green }
function Write-Info { Write-Host $args -ForegroundColor Cyan }
function Write-Warning { Write-Host $args -ForegroundColor Yellow }
function Write-Error { Write-Host $args -ForegroundColor Red }

Write-Success "🚀 OnlyFix Windows Setup"
Write-Success "========================`n"

# Check admin privileges
$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Error "❌ Administrator privileges required!"
    Write-Warning "Right-click PowerShell and select 'Run as Administrator'`n"
    Write-Info "Or run: Start-Process powershell -Verb RunAs -ArgumentList '-ExecutionPolicy Bypass -File scripts\setup-windows.ps1'"
    exit 1
}

# Hosts file configuration
$hostsPath = "$env:SystemRoot\System32\drivers\etc\hosts"
$hostsBackup = "$hostsPath.backup.$(Get-Date -Format 'yyyyMMdd_HHmmss')"

Write-Info "📋 Creating hosts file backup..."
try {
    Copy-Item $hostsPath $hostsBackup -Force
    Write-Success "✅ Backup saved: $hostsBackup`n"
} catch {
    Write-Error "❌ Failed to backup hosts file: $_"
    exit 1
}

# OnlyFix hosts entries
$hostsEntries = @'

# OnlyFix Project - Docker Services
127.0.1.1       onlyfix.local
127.0.1.2       db.onlyfix.local
127.0.1.3       mailpit.onlyfix.local
127.0.1.4       node.onlyfix.local
127.0.1.5       phpmyadmin.onlyfix.local
'@

# Check if entries already exist
$hostsContent = Get-Content $hostsPath -Raw

if ($hostsContent -match "OnlyFix Project") {
    Write-Warning "⚠️  OnlyFix hosts entries already exist!"
    $response = Read-Host "Overwrite? (y/N)"
    
    if ($response -eq "y" -or $response -eq "Y") {
        try {
            # Remove old entries
            $hostsContent = $hostsContent -replace "(?ms)# OnlyFix Project.*?(?=\r?\n\r?\n|\Z)", ""
            Set-Content -Path $hostsPath -Value $hostsContent.Trim()
            
            # Add new entries
            Add-Content -Path $hostsPath -Value $hostsEntries
            Write-Success "✅ Hosts file updated!`n"
        } catch {
            Write-Error "❌ Failed to update hosts file: $_"
            exit 1
        }
    } else {
        Write-Warning "⏭️  Skipping hosts modification`n"
    }
} else {
    try {
        Add-Content -Path $hostsPath -Value $hostsEntries
        Write-Success "✅ Hosts file updated!`n"
    } catch {
        Write-Error "❌ Failed to update hosts file: $_"
        exit 1
    }
}

# Flush DNS cache
Write-Info "🔄 Flushing DNS cache..."
try {
    ipconfig /flushdns | Out-Null
    Write-Success "✅ DNS cache cleared!`n"
} catch {
    Write-Warning "⚠️  DNS flush failed (non-critical): $_`n"
}

# Test hosts configuration
Write-Info "🧪 Testing hosts configuration...`n"

$testDomains = @(
    "onlyfix.local",
    "db.onlyfix.local",
    "mailpit.onlyfix.local",
    "node.onlyfix.local",
    "phpmyadmin.onlyfix.local"
)

foreach ($domain in $testDomains) {
    try {
        $result = Test-Connection -ComputerName $domain -Count 1 -Quiet -ErrorAction Stop
        if ($result) {
            Write-Success "  ✅ $domain"
        } else {
            Write-Warning "  ⚠️  $domain (not responding)"
        }
    } catch {
        Write-Error "  ❌ $domain (error: $($_.Exception.Message))"
    }
}

# Check Docker
Write-Info "`n🐳 Checking Docker..."
try {
    $dockerVersion = docker --version 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Success "✅ Docker installed: $dockerVersion"
    } else {
        throw "Docker command failed"
    }
    
    $composeVersion = docker-compose --version 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Success "✅ Docker Compose installed: $composeVersion`n"
    } else {
        throw "Docker Compose command failed"
    }
} catch {
    Write-Error "❌ Docker not installed or not running!"
    Write-Warning "Install Docker Desktop: https://www.docker.com/products/docker-desktop`n"
    Write-Error "Error: $_"
    exit 1
}

# Check .env file
Write-Info "⚙️  Checking .env file..."
if (Test-Path "onlyfix\.env") {
    Write-Success "✅ .env file exists`n"
} else {
    if (Test-Path "onlyfix\.env.example") {
        try {
            Copy-Item "onlyfix\.env.example" "onlyfix\.env"
            Write-Success "✅ .env file created from .env.example`n"
        } catch {
            Write-Warning "⚠️  Failed to create .env file: $_`n"
        }
    } else {
        Write-Warning "⚠️  .env.example not found!`n"
    }
}

# Check Node.js
Write-Info "📦 Checking Node.js..."
try {
    $nodeVersion = node --version 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Success "✅ Node.js installed: $nodeVersion"
        
        # Install NPM dependencies on host (for IntelliSense)
        Write-Info "📦 Installing NPM dependencies on host (for VS Code IntelliSense)..."
        Push-Location "onlyfix"
        try {
            npm install 2>&1 | Out-Null
            if ($LASTEXITCODE -eq 0) {
                Write-Success "✅ NPM dependencies installed on host`n"
            } else {
                Write-Warning "⚠️  NPM install failed (non-critical): $($Error[0])`n"
            }
        } catch {
            Write-Warning "⚠️  NPM install failed (non-critical): $_`n"
        }
        Pop-Location
    } else {
        throw "Node command failed"
    }
} catch {
    Write-Warning "⚠️  Node.js not installed!"
    Write-Info "Install Node.js: https://nodejs.org/`n"
    Write-Warning "Note: Docker will still work, but VS Code IntelliSense may not work properly.`n"
}

# Summary
Write-Success "🎉 Setup completed!`n"
Write-Info "Next steps:"
Write-Host "  make build       - Build Docker images"
Write-Host "  make start       - Start containers"
Write-Host "  make install     - Install dependencies"
Write-Host "  make migrate     - Run database migrations"
Write-Host "`nOr simply run:"
Write-Warning "  make init        - Complete automatic setup`n"
Write-Info "Access URLs:"
Write-Host "  🌐 http://onlyfix.local"
Write-Host "  📧 http://mailpit.onlyfix.local:8025"
Write-Host "  🗄️  http://phpmyadmin.onlyfix.local:8080`n"
