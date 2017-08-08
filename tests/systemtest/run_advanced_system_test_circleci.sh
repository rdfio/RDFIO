#!/bin/bash
echo "Starting advanced system test ...";

mysql -u root circle_test < emptydb.sql

php ../../../../maintenance/importDump.php advanced_content_initial.xml

php ../../maintenance/importRdf.php --in data/testdata_advanced.ttl
php ../../../../maintenance/dumpBackup.php --current > advanced_actual.xml

cat advanced_expected.xml | sed -r 's#(</text>|</title>)#\n\1#' | sed 's#<title>#<title>\n#' | grep -vP '[<>]' > advanced_content_expected.xml
cat advanced_actual.xml | sed -r 's#(</text>|</title>)#\n\1#' | sed 's#<title>#<title>\n#' | grep -vP '[<>]' | sed -r 's/(Blank[\ _]node[\ _])[a-z1-9]+/\1123abc/g' > advanced_content_actual.xml

if ! diff -q advanced_content_{expected,actual}.xml &>/dev/null; then
	>&2 echo "ERROR: Files differ in advanced system test!" && echo "For details, check with diff advanced_content_{expected,actual}.xml in Rdfio/tests/systemtest"
	exit 1;
else
	echo "Advanced system test passed!";
fi;
