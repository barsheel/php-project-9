cd /d "%~dp0"
docker run ^
	--name "hexlet-project-3" ^
	-v ".:/hexlet-code" ^
	-p 8000:8000 ^
	-d barsheel_php_image_v5 ^
	tail -f /dev/null