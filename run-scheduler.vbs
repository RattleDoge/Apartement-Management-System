Set objShell = CreateObject("WScript.Shell")
objShell.Run """C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"" ""C:\laragon\www\TA\artisan"" schedule:run", 0, False
Set objShell = Nothing
