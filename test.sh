#!/usr/bin/env sh

set -x

mkdir -p build-reports

echo "Checking PHP code compatibility from v7.2 and up"
vendor/bin/phpcs -sp --colors \
	--standard=WordPress \
	--basepath=. \
	--ignore=vendor \
	--extensions=php \
	--parallel=$(nproc --all) \
	--report=full \
	--report-file=./build-reports/php-code-standard.txt \
	wp-content/themes/twentytwenty/


echo "Checking PHP code compatibility from v7.2 and up"
vendor/bin/phpcs -p \
	--standard=PHPCompatibilityWP \
	--basepath=. \
	--ignore=vendor \
	--extensions=php \
	--runtime-set testVersion 7.2- \
	--parallel=$(nproc --all) \
	--report=full \
	--report-file=./build-reports/php-code-compatibility.txt \
	wp-content/themes/twentytwenty/


echo "Running PHP Mess Detector"
vendor/bin/phpmd --reportfile ./build-reports/php-mess-detector.html \
	wp-content/themes/twentytwenty/ \
	html \
	cleancode,unusedcode


echo "Running PHP tests"
vendor/bin/phpunit \
	--testdox-html ./build-reports/phpunit-test.html \
	./tests

