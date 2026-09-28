@echo off
echo Setting up ArtM_ShortVideos module...
cd /d e:\backups\golfoy\golfoylive

echo.
echo Enabling module...
php bin/magento module:enable ArtM_ShortVideos

echo.
echo Running setup upgrade...
php bin/magento setup:upgrade

echo.
echo Compiling DI...
php bin/magento setup:di:compile

echo.
echo Cleaning cache...
php bin/magento cache:clean
php bin/magento cache:flush

echo.
echo Deploying static content...
php bin/magento setup:static-content:deploy -f en_US

echo.
echo Setup complete!
pause
