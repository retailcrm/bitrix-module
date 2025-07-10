ROOT_DIR=$(shell dirname $(realpath $(lastword $(MAKEFILE_LIST))))

test: prepare_module
	composer tests

prepare_module:
	composer pre-module-install

deps:
ifneq ($(NOT_USE_VENDOR),1)
	composer install
endif

install_bitrix: download_bitrix
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
	wget --progress=dot -e dotbytes=10M -O /tmp/$(BITRIX_EDITION).tar.gz https://www.1c-bitrix.ru/download/$(BITRIX_EDITION).tar.gz
	mkdir -p $(BITRIX_PATH)
	chmod -R 777 $(BITRIX_PATH)
	tar -xf /tmp/$(BITRIX_EDITION).tar.gz -C $(BITRIX_PATH)
	rm /tmp/$(BITRIX_EDITION).tar.gz
	sudo chown -R www-data:www-data /var/lib/php/sessions
endif

build_release:
ifneq ($(LAST_TAG),$(RELEASE_TAG))
	git diff --name-status $(LAST_TAG) HEAD > $(ROOT_DIR)/release/diff
	php bin/build-release
	bash bin/build $(CURRENT_VERSION) $(ROOT_DIR)/release/
else
	@exit 0
endif

cleanup:
	@rm -rf $(ROOT_DIR)/release/$(CURRENT_VERSION)
	@rm $(ROOT_DIR)/release/$(CURRENT_VERSION).tar.gz

run_local_tests:
	docker-compose up -d --build
	docker exec app_test make install_bitrix deps test
	docker-compose down
