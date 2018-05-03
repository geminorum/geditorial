@ECHO OFF
Title gEditorial Package Builder
md .build
cd .build
ECHO GIT -----------------------------------------------------------------------
CALL git clone https://github.com/geminorum/geditorial .
ECHO COMPOSER ------------------------------------------------------------------
CALL composer install --no-dev --optimize-autoloader --prefer-dist -v
ECHO Yarn ----------------------------------------------------------------------
CALL yarn install
ECHO BUILD ---------------------------------------------------------------------
CALL yarn run build
ECHO FINISHED ------------------------------------------------------------------
cd ..
