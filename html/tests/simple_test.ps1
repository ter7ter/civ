$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir
$phpunitCmd = "php `"$ScriptDir\phpunit.phar`""
$workingTests = @(
    "tests\unit\InfiniteLoopTest.php"
)
$TimeoutSeconds = 15

Set-Location $ProjectRoot

foreach ($testFile in $workingTests) {
    $fullCommand = "$phpunitCmd --configuration tests\phpunit.xml --no-coverage `"$testFile`""
    try {
        $process = Start-Process -FilePath "cmd.exe" -ArgumentList "/c $fullCommand" -NoNewWindow -PassThru
        if ($process | Wait-Process -Timeout $TimeoutSeconds) {
            Write-Host "Test finished within timeout."
        } else {
            Write-Host "Test timed out."
            Stop-Process -Id $process.Id -Force
        }
    } catch {
        Write-Host "An error occurred: $_"
    }
}