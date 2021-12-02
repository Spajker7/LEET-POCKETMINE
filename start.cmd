@echo off
TITLE PocketMine-MP server software for Minecraft: Pocket Edition
cd /d %~dp0

set PHP_BINARY=

where /q php.exe
if %ERRORLEVEL%==0 (
	set PHP_BINARY=php
)

if exist bin\php\php.exe (
	rem always use the local PHP binary if it exists
	set PHPRC=""
	set PHP_BINARY=bin\php\php.exe
)

if "%PHP_BINARY%"=="" (
	echo Couldn't find a PHP binary in system PATH or %~dp0\bin\php
	echo Please refer to the installation instructions at https://doc.pmmp.io/en/rtfd/installation.html
	pause
	exit 1
)

if exist src\pocketmine\PocketMine.php (
	set POCKETMINE_FILE=src\pocketmine\PocketMine.php
) else (
	echo src\pocketmine\PocketMine.php not found
	echo Downloads can be found at https://github.com/pmmp/PocketMine-MP/releases
	pause
	exit 1
)

if exist bin\mintty.exe (
	start "" bin\mintty.exe -o Columns=88 -o Rows=32 -o AllowBlinking=0 -o FontQuality=3 -o Font="Consolas" -o FontHeight=10 -o CursorType=0 -o CursorBlinks=1 -h error -t "PocketMine-MP" -i bin/pocketmine.ico -w max %PHP_BINARY% %POCKETMINE_FILE% --enable-ansi %*
) else (
	REM pause on exitcode != 0 so the user can see what went wrong
	REM %PHP_BINARY% -c bin\php -dxdebug.remote_enable=1 -dxdebug.remote_mode=req -dxdebug.remote_port=9000 -dxdebug.remote_host=127.0.0.1 %POCKETMINE_FILE% %* || pause
	%PHP_BINARY% -c bin\php -dxdebug.mode=debug %POCKETMINE_FILE% %* || pause
)
