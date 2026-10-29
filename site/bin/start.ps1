# Lance le terminal L.E.R.N.A. : le serveur web local (PHP), le pont vers la carte Arduino, et si besoin le broker Mosquitto.
# Usage : powershell -ExecutionPolicy Bypass -File site\bin\start.ps1 [-Port 8000] [-Local] [-Broker]
#   -Local  : seul ce PC peut ouvrir le site. Par défaut le site est ouvert au réseau local (téléphone, page Game Master).
#   -Broker : lance aussi Mosquitto avec mosquitto\mosquitto.conf (sauf s'il tourne déjà pour le réseau local).
param(
    [int]$Port = 8000,
    [switch]$Local,
    [switch]$Lan,   # ancien nom, ignoré : le réseau local est maintenant le comportement par défaut
    [switch]$Broker
)

if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    Write-Host "PHP est introuvable. Installez PHP 8 (zip sur windows.php.net) et ajoutez son dossier au PATH." -ForegroundColor Red
    Write-Host "Vérification : php -v"
    exit 1
}
$manque = @('pdo_sqlite', 'mbstring') | Where-Object { -not (php -r "echo extension_loaded('$_') ? 'oui' : '';") }
if ($manque) {
    Write-Host "Extensions PHP manquantes : $($manque -join ', '). Dans php.ini, retirez le ';' devant extension=$($manque -join ' et extension=')." -ForegroundColor Red
    exit 1
}

# Le pont parle au broker dont l'adresse est dans secrets.h : c'est celui auquel la carte se connecte.
# (Le service Windows de Mosquitto n'écoute que sur localhost : sans cela, le site et la carte parleraient à deux brokers différents.)
if (-not $env:LERNA_MQTT_HOST) {
    $secrets = Join-Path $PSScriptRoot '..\..\carte\include\secrets.h'
    if (Test-Path $secrets) {
        $m = Select-String -Path $secrets -Pattern '#define\s+MQTT_SERVER_IP\s+"([^"]+)"' | Select-Object -First 1
        if ($m) { $env:LERNA_MQTT_HOST = $m.Matches[0].Groups[1].Value }
    }
}
if ($env:LERNA_MQTT_HOST) { Write-Host "Broker MQTT : $($env:LERNA_MQTT_HOST)" }
else { Write-Host "Broker MQTT : localhost (pas de secrets.h trouvé)" }

if ($Broker) {
    $mosq = 'C:\Program Files\mosquitto\mosquitto.exe'
    $conf = Join-Path $PSScriptRoot '..\..\mosquitto\mosquitto.conf'
    $dejaLa = Get-NetTCPConnection -State Listen -LocalPort 1883 -ErrorAction SilentlyContinue | Where-Object { $_.LocalAddress -eq '0.0.0.0' }
    if ($dejaLa) {
        Write-Host "Un broker écoute déjà sur le réseau (port 1883) : je n'en lance pas un deuxième."
    } elseif (-not (Test-Path $mosq)) {
        Write-Host "Mosquitto est introuvable dans C:\Program Files\mosquitto. Installez-le (winget install EclipseFoundation.Mosquitto)." -ForegroundColor Red
    } else {
        Start-Process -FilePath $mosq -ArgumentList '-c', "`"$conf`"", '-v'
        Write-Host "Broker Mosquitto lancé (fenêtre séparée)."
    }
}

# Le pont : relie les boutons et l'alarme de la carte à la partie. Fenêtre séparée, réduite.
$pont = Start-Process -FilePath 'php' -ArgumentList "`"$(Join-Path $PSScriptRoot 'bridge.php')`"" -WindowStyle Minimized -PassThru
Write-Host "Pont carte <-> jeu lancé (fenêtre réduite)."

$bind = if ($Local) { '127.0.0.1' } else { '0.0.0.0' }
# l'adresse du PC sur le réseau local : celle de la carte réseau qui sort vers Internet (ou le partage de connexion)
$route = Get-NetRoute -DestinationPrefix '0.0.0.0/0' -ErrorAction SilentlyContinue | Sort-Object RouteMetric | Select-Object -First 1
$ipLan = if ($route) { (Get-NetIPAddress -AddressFamily IPv4 -InterfaceIndex $route.InterfaceIndex -ErrorAction SilentlyContinue | Select-Object -First 1).IPAddress } else { $null }
Write-Host "Terminal L.E.R.N.A. : http://127.0.0.1:$Port  (mot de passe de départ : voir site\README.md)"
Write-Host "Page Game Master    : http://127.0.0.1:$Port/gm.html"
if (-not $Local -and $ipLan) {
    Write-Host ""
    Write-Host "A ouvrir sur les autres appareils (meme Wi-Fi que ce PC) :" -ForegroundColor Cyan
    Write-Host "  Telephone de Kovac : http://${ipLan}:$Port/telephone.html" -ForegroundColor Cyan
    Write-Host "  Game Master        : http://${ipLan}:$Port/gm.html" -ForegroundColor Cyan
    Write-Host "  (Windows peut demander d'autoriser PHP : cocher aussi les reseaux publics si c'est un partage de connexion.)"
    Write-Host ""
} elseif (-not $Local) { Write-Host "Adresse du PC introuvable : verifiez la connexion Wi-Fi (commande ipconfig)." -ForegroundColor Yellow }
Write-Host "Ctrl+C pour arrêter."
try {
    php -S "${bind}:$Port" -t (Join-Path $PSScriptRoot '..\public')
} finally {
    if ($pont -and -not $pont.HasExited) { Stop-Process -Id $pont.Id -Force }
}
