#!/bin/bash
echo "Starting advanced system test ...";

mysql -u root circle_test < emptydb.sql

php ../../../../maintenance/importDump.php initial_content_advanced.xml

php ../../maintenance/importRdf.php --in data/testdata_advanced.ttl
php ../../../../maintenance/dumpBackup.php --current > actual_advanced.xml

cat expected_advanced.xml | sed -r 's#(</text>|</title>)#\n\1#' | sed 's#<title>#<title>\n#' | grep -vP '[<>]' > expected_content_advanced.xml
cat actual_advanced.xml | sed -r 's#(</text>|</title>)#\n\1#' | sed 's#<title>#<title>\n#' | grep -vP '[<>]' > actual_content_advanced.xml

if ! diff -q {expected,actual}_content_advanced.xml &>/dev/null; then
	>&2 echo "ERROR: Files differ in advanced system test!" && echo "For details, check with diff {expected,actual}_content_advanced.xml in Rdfio/tests/systemtest"
	exit 1;
else
	echo "Advanced system test passed!";
fi;
