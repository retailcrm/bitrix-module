#!/usr/bin/env bash

if [ -d $TRAVIS_BUILD_DIR ]; then
	BITRIX_PATH="$TRAVIS_BUILD_DIR/bitrix"
fi

download() {
	wget http://download.retailcrm.pro/modules/bitrix/bitrix.tar.gz

	mkdir bitrix
	tar -xf bitrix.tar.gz -C bitrix
	rm bitrix.tar.gz
}

create_db() {
	mysqladmin create $DB_BITRIX_NAME --user="$DB_BITRIX_LOGIN" --password="$DB_BITRIX_PASS"
	mysql -user="$DB_BITRIX_LOGIN" -password="$DB_BITRIX_PASS" $DB_BITRIX_NAME < $BITRIX_PATH/dump.sql
}

download
create_db
