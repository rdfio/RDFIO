#!/bin/bash
rm actual_content.xml
mysql -u root smw < emptydb.sql &>/dev/null
php ../../maintenance/importRdf.php --indata data/testdata.ttl &>/dev/null
php ../../../../maintenance/dumpBackup.php --current | sed -r 's#(</text>|</title>)#\n\1#' | sed 's#<title>#<title>\n#' | grep -vP '[<>]' > actual_content.xml &>/dev/null
if ! diff -q {expected,actual}_content.xml &>/dev/null; then
	>&2 echo "ERROR: Files differ!" && echo "For details, check with diff expected_content.xml actual_content.xml in Rdfio/tests/systemtest"
	exit 1;
else
	echo "System test passed!";
fi;
