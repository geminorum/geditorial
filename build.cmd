@ECHO OFF
Title gEditorial Package Builder
md .build
cd .build
ECHO GIT -----------------------------------------------------------------------
CALL git clone https://github.com/geminorum/geditorial .
ECHO COMPOSER ------------------------------------------------------------------
CALL composer install --no-dev --optimize-autoloader --prefer-dist -v
ECHO NPM -----------------------------------------------------------------------
CALL npm install
ECHO BUILD ---------------------------------------------------------------------
CALL npm run build
ECHO FINISHED ------------------------------------------------------------------
cd ..
