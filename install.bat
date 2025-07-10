@echo off
echo ========================================
echo Installation de CHIC AFFILIATE
echo ========================================
echo.

echo 1. Verification de MySQL...
mysql --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERREUR: MySQL n'est pas accessible. Verifiez que MySQL est demarre.
    pause
    exit /b 1
)
echo OK: MySQL est accessible
echo.

echo 2. Creation de la base de donnees...
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS chic_affiliate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if %errorlevel% neq 0 (
    echo ERREUR: Impossible de creer la base de donnees.
    pause
    exit /b 1
)
echo OK: Base de donnees creee
echo.

echo 3. Import du fichier SQL...
if not exist "complete_database.sql" (
    echo ERREUR: Le fichier complete_database.sql n'existe pas.
    pause
    exit /b 1
)

mysql -u root -p chic_affiliate < complete_database.sql
if %errorlevel% neq 0 (
    echo ATTENTION: L'import s'est termine avec des avertissements.
    echo Cela peut etre normal si certaines tables existent deja.
) else (
    echo OK: Import reussi
)
echo.

echo 4. Verification de l'installation...
mysql -u root -p -e "USE chic_affiliate; SELECT COUNT(*) as admins FROM admins; SELECT COUNT(*) as products FROM products; SELECT COUNT(*) as categories FROM categories;"
echo.

echo ========================================
echo Installation terminee !
echo ========================================
echo.
echo Informations de connexion:
echo - URL: http://localhost/adnane1/
echo - Admin: admin@chic-affiliate.com
echo - Mot de passe: password
echo.
echo Test: http://localhost/adnane1/test_database.php
echo.
pause 