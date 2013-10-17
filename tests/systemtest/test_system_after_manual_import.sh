#!/bin/bash
php ../../../../maintenance/dumpBackup.php --current > out.xml
diff -y --suppress-common-lines default.xml out.xml|grep -v "<timestamp>"
