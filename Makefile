test: prepare_module
	@php -d short_open_tag=On vendor/bin/phpunit -c phpunit.xml.dist

prepare_module:
	@composer pre-module-install
