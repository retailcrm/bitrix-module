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
ifeq ("$(BITRIX_DOWNLOAD_LINK)","http://download.retailcrm.pro/modules/bitrix/bitrix_staging_dump_small_business.tar.gz")
	@echo "===== Writing config"
	@echo "<config><skipInstallModules>eshopapp</skipInstallModules><LANGUAGE_ID>ru</LANGUAGE_ID><INSTALL_CHARSET>windows-1251</INSTALL_CHARSET></config>" > install.config
	rm $(BITRIX_PATH)/bitrix/footer.php
	rm $(BITRIX_PATH)/bitrix/header.php
	rm $(BITRIX_PATH)/bitrix/license_key.php
endif
	@echo "===== Installing Bitrix..."
	@php bin/bitrix-install db_type
	@php bin/bitrix-install requirement
	@php bin/bitrix-install db_create
	@php bin/enable_debugging $(BITRIX_PATH)
	@php bin/bitrix-install main_module
	@php bin/bitrix-install module
	@php bin/bitrix-install admin
	@php bin/bitrix-install load_module
	@php bin/bitrix-install load_module_action
	@php bin/bitrix-install finish

download_bitrix:
ifeq ("$(wildcard $(BITRIX_PATH)/bitrix/php_interface/dbconn.php)","")
	wget --progress=dot -e dotbytes=10M -O /tmp/bitrix.tar.gz $(BITRIX_DOWNLOAD_LINK)
	mkdir -p $(BITRIX_PATH)
	chmod -R 777 $(BITRIX_PATH)
	tar -xf /tmp/bitrix.tar.gz -C $(BITRIX_PATH)
	rm /tmp/bitrix.tar.gz
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
