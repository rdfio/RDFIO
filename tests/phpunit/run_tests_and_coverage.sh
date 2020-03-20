#!/bin/bash
php ../../../../tests/phpunit/phpunit.php --use-normal-tables --configuration=suite.rdfio.xml --coverage-clover=coverage.xml
