#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" > /dev/null && pwd )"

ssh yqb.dev@staging-25038.prod.hosting.acquia.com -p 22 "/var/www/html/yqb.dev/vendor/drush/drush/drush sql-dump" | docker exec -i yqb_mysql mysql -u yqb -pyqb yqb &> /dev/null

echo -ne "├─ Voulez-vous copier les uploads? [o/N] "
read copy_uploads

if [ "$copy_uploads" == "o" -o "$copy_uploads" == "O" ]; then
    if [ ! -f $DIR/../docroot/sites/default/files ]; then
        mkdir $DIR/../docroot/sites/default/files &> /dev/null
    fi
    rsync -rauve "ssh -p 22" yqb.dev@staging-25038.prod.hosting.acquia.com:/var/www/html/yqbdev/docroot/sites/default/files/* $DIR/../docroot/sites/default/files/. &> /dev/null
fi