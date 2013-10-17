#!/bin/bash
php ../../../../maintenance/dumpBackup.php --current > out.xml
diff -y --suppress-common-lines after_manual_import.xml out.xml|grep -v "<timestamp>"
