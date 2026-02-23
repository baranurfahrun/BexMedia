$path = "$env:USERPROFILE\Downloads\sik.sql"
$out  = "C:\xampp\htdocs\BexMedia\sql\pegawai_extract.sql"

$reader = [System.IO.StreamReader]::new($path)
$found  = $false
$count  = 0
$sb     = [System.Text.StringBuilder]::new()

while (-not $reader.EndOfStream) {
    $line = $reader.ReadLine()

    if ($line -match "CREATE TABLE ``pegawai``") {
        $found = $true
    }

    if ($found) {
        [void]$sb.AppendLine($line)
        $count++
        if ($count -gt 3 -and $line -match "ENGINE=") {
            break
        }
    }
}

$reader.Close()
[System.IO.File]::WriteAllText($out, $sb.ToString())
Write-Host "Done - $count lines"
