#!/bin/sh
echo "Fetch Post started : $(date)"
php /srv/app/bin/console app:fetch
echo "Fetch Post finished: $(date)"
