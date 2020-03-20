#!/bin/bash
echo "Starting simple system test..."

mysql -u root --password=changethis circle_test < emptydb.sql

php ../../maintenance/importRdf.php --in data/testdata.ttl
php ../../../../maintenance/dumpBackup.php --current | sed -r 's#(</text>|</title>)#\n\1#' | sed 's#<title>#<title>\n#' | grep -vP '[<>]' > simple_content_actual.xml
if ! diff -q simple_content_{expected,actual}.xml &>/dev/null; then
	>&2 echo "ERROR: Files differ!" && echo "For details, check with diff simple_content_{expected,actual}.xml in Rdfio/tests/systemtest";
	exit 1;
else
	echo "System test passed!";
fi;
