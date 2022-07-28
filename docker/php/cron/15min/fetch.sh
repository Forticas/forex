#!/bin/sh
PHP=`which php`
echo "Fetch Post started : $(date)"
$PHP /srv/app/bin/console app:fetch
echo "Fetch Post finished: $(date)"
