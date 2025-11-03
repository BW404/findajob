@echo off
echo Running NIN Verification Migration...
echo.

cd /d E:\XAMPP\htdocs\findajob

E:\XAMPP\php\php.exe run-nin-migration.php

echo.
echo Done! Press any key to exit...
pause > nul
