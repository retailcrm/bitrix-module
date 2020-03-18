ROOT_DIR=$(shell dirname $(realpath $(lastword $(MAKEFILE_LIST))))

test: prepare_module
ifeq ($(NOT_USE_VENDOR),1)
	composer tests7
else
	composer tests
endif

prepare_module: deps
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

create_db:
	echo "USE mysql;\nUPDATE user SET password=PASSWORD('root') WHERE user='root';\nFLUSH PRIVILEGES;\n" | mysql -u root
	mysqladmin create $(DB_BITRIX_NAME) --user=$(DB_BITRIX_LOGIN) --password=$(DB_BITRIX_PASS)

build_release: build_release_dir
	bash bin/build $(VERSION) $(ROOT_DIR)/release/

build_release_dir: build_diff_file
	php bin/build-release

build_diff_file:
	git diff --name-status $(LAST_TAG) HEAD > $(ROOT_DIR)/release/diff

cleanup:
	@rm $(ROOT_DIR)/release/$(VERSION)
	@rm $(ROOT_DIR)/release/$(VERSION).tar.gz

# docker commands
install:
	docker-compose exec bitrix make bitrix_install

run_tests:
	docker-compose exec bitrix make test
