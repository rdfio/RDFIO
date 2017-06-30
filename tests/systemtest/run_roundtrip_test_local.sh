#!/bin/bash
echo "Starting simple system test..."

expected="roundtrip_content_expected.nt"
actual="roundtrip_content_actual.nt"
actual_filtered="roundtrip_content_actual_filtered.nt"

rm $actual $actual_filtered
mysql -u root smw < emptydb.sql
php ../../maintenance/importRdf.php --in data/testdata.nt
php ../../maintenance/exportRdf.php --origuris --format ntriples --out $actual

cat $actual | grep -v 'swivt' | grep -v 'localhost' | grep -vP '#(sameAs|label|type|equivalentProperty|subClassOf)' | sort > $actual_filtered

if ! diff -q $expected $actual_filtered &>/dev/null; then
	>&2 echo "ERROR: Files differ!" && echo "For details, check with diff $expected $actual_filtered in Rdfio/tests/systemtest"
	exit 1;
else
	echo "System test passed!";
fi;
