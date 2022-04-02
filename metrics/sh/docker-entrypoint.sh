#!/bin/sh
exporter_port=9137
cd /app/metrics && php -S 0.0.0.0:$exporter_port index.php &> /dev/null &
/usr/local/smproxy/SMProxy start --console
