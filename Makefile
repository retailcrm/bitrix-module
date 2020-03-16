ROOT_DIR=$(shell dirname $(realpath $(lastword $(MAKEFILE_LIST))))

test: prepare_module
	@composer tests

prepare_module:
	@composer pre-module-install

bitrix_install: download_bitrix
	@echo "===== Installing Bitrix..."
	@php bin/bitrix-install db_type
	@php bin/bitrix-install requirement
	@php bin/bitrix-install db_create
	@php bin/bitrix-install main_module
	@php bin/bitrix-install module
	@php bin/bitrix-install admin
	@php bin/bitrix-install load_module
	@php bin/bitrix-install load_module_action
	@php bin/bitrix-install finish

download_bitrix:
ifeq ("$(wildcard $(BITRIX_PATH)/bitrix/php_interface/dbconn.php)","")
	@wget -O /tmp/small_business_encode.tar.gz https://www.1c-bitrix.ru/download/small_business_encode.tar.gz
	@mkdir -p $(BITRIX_PATH)
	@chmod -R 755 $(BITRIX_PATH)
	@tar -xf /tmp/small_business_encode.tar.gz -C $(BITRIX_PATH)
	@rm /tmp/small_business_encode.tar.gz
endif

create_db:
	echo "USE mysql;\nUPDATE user SET password=PASSWORD('root') WHERE user='root';\nFLUSH PRIVILEGES;\n" | mysql -u root
	@mysqladmin create $(DB_BITRIX_NAME) --user=$(DB_BITRIX_LOGIN) --password=$(DB_BITRIX_PASS)

# docker commands
install:
	@docker-compose exec bitrix make bitrix_install

run_tests:
	@docker-compose exec bitrix make test
