#Requires -Version 5.1
param(
  [string]$DbHost = "127.0.0.1",
  [string]$DbPort = "3306",
  [string]$DbName = "typeforge",
  [string]$DbUser = "root",
  [string]$OutDir = "backups"
)
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
New-Item -ItemType Directory -Path $OutDir -Force | Out-Null
$out = Join-Path $OutDir "$DbName-$stamp.sql"
& "C:\xampp\mysql\bin\mysqldump.exe" -h $DbHost -P $DbPort -u $DbUser $DbName | Out-File -Encoding utf8 $out
Write-Output "Backup written to $out"
