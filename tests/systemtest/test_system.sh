#!/bin/bash
php ../../../../maintenance/dumpBackup.php --current > out.xml
diff default.xml out.xml|less -S
