#!/usr/bin/env bash
# The loop's gate: every flow's log must prove itself, and the app suite must stay green.
set -uo pipefail
cd /Users/akihito/git/BeMart || exit 2

export DATABASE_URL='mysql://root@127.0.0.1:3306/eccubedb_test?charset=utf8mb4&serverVersion=8.0.0'
export CACHE_DSN='redis://127.0.0.1:6379'
export APP_CONTEXT=cli-fake-hal-app

# Pin the interpreter: this project is tested on 8.4, and a loop that judges with whatever `php`
# a parent process happened to export is judging something else.
PHP=${PHP:-/opt/homebrew/opt/php@8.4/bin/php}
[ -x "$PHP" ] || { echo "no php at $PHP"; exit 2; }

status=0
for flow in help help-revalidate products-app products-page product-stock products-corpus-tag shopping-complete customer-profile; do
    if "$PHP" var/loop/verify-cache.php "$flow" > "var/loop/last-$flow.txt" 2>&1; then
        printf 'oracle %-14s ok\n' "$flow"
    else
        printf 'oracle %-14s FAIL\n' "$flow"
        tail -5 "var/loop/last-$flow.txt"
        status=1
    fi
done

if "$PHP" ./vendor/bin/phpunit --no-coverage > var/loop/last-phpunit.txt 2>&1; then
    printf 'phpunit        ok  %s\n' "$(tail -1 var/loop/last-phpunit.txt)"
else
    printf 'phpunit        FAIL\n'
    grep -E "^[0-9]+\)|Tests:" var/loop/last-phpunit.txt | head -6
    status=1
fi

exit $status
