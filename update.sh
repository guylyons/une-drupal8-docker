#!/bin/bash
#

echo "\e[32mPlease enter docker container id:"
read CONTAINER

echo "\e[32mRunning composer install..."
docker exec -i $CONTAINER composer install 

echo "\e[32mImporting config with Drush..."
docker exec -i $CONTAINER ./vendor/bin/drush -y cim

echo "\e[32mYarn build steps..."
cd code/themes/custom/une
yarn install
yarn build

echo "\e[32mDone!"
