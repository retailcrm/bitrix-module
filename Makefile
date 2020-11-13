ROOT_DIR=$(shell dirname $(realpath $(lastword $(MAKEFILE_LIST))))

test: prepare_module
	composer tests

prepare_module:
	composer pre-module-install

deps:
ifneq ($(NOT_USE_VENDOR),1)
	composer install
endif

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
	wget -O /tmp/$(BITRIX_EDITION).tar.gz https://www.1c-bitrix.ru/download/$(BITRIX_EDITION).tar.gz
	mkdir -p $(BITRIX_PATH)
	chmod -R 777 $(BITRIX_PATH)
	tar -xf /tmp/$(BITRIX_EDITION).tar.gz -C $(BITRIX_PATH)
	rm /tmp/$(BITRIX_EDITION).tar.gz
endif

build_release:
ifneq ($(LAST_TAG),$(CURRENT_VERSION))
	git diff --name-status $(LAST_TAG) HEAD > $(ROOT_DIR)/release/diff
	php bin/build-release
	bash bin/build $(VERSION) $(ROOT_DIR)/release/
else
	@exit 0
endif

cleanup:
	@rm -rf $(ROOT_DIR)/release/$(VERSION)
	@rm $(ROOT_DIR)/release/$(VERSION).tar.gz

# docker commands
install:
	docker-compose exec bitrix make bitrix_install

run_tests:
	docker-compose exec bitrix make test
