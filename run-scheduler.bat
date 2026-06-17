@echo off
"C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" "C:\laragon\www\TA\artisan" schedule:run >> "C:\laragon\www\TA\storage\logs\scheduler.log" 2>&1
