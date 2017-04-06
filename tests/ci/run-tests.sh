#! /bin/bash
set -ex

cd /home/ubuntu/w/extensions/Rdfio/tests/phpunit/
./run_tests_and_coverage.sh
mv coverage.xml $HOME/RDFIO/
