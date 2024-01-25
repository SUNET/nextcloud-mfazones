# SPDX-FileCopyrightText: Bernhard Posselt <dev@bernhard-posselt.com>
# SPDX-License-Identifier: AGPL-3.0-or-later

# Generic Makefile for building and packaging a Nextcloud app which uses npm and
# Composer.
#
# Dependencies:
# * make
# * which
# * curl: used if phpunit and composer are not installed to fetch them from the web
# * tar: for building the archive
# * npm: for building and testing everything JS
#
# If no composer.json is in the app root directory, the Composer step
# will be skipped. The same goes for the package.json which can be located in
# the app root or the js/ directory.
#
# The npm command by launches the npm build script:
#
#    npm run build
#
# The npm test command launches the npm test script:
#
#    npm run test
#
# The idea behind this is to be completely testing and build tool agnostic. All
# build tools and additional package managers should be installed locally in
# your project, since this won't pollute people's global namespace.
#
# The following npm scripts in your package.json install and update the bower
# and npm dependencies and use gulp as build system (notice how everything is
# run from the node_modules folder):
#
#    "scripts": {
#        "test": "node node_modules/gulp-cli/bin/gulp.js karma",
#        "prebuild": "npm install && node_modules/bower/bin/bower install && node_modules/bower/bin/bower update",
#        "build": "node node_modules/gulp-cli/bin/gulp.js"
#    },
app_name=mfazones
cert_dir=$(HOME)/.nextcloud/certificates
project_dir=$(CURDIR)/$(app_name)
build_dir=$(project_dir)/build/artifacts
build_tools_dir=$(project_dir)/build/tools
sign_dir=$(build_dir)/sign
version+=0.0.2

all: appstore
release: appstore

sign: package
	docker run --rm --volume $(cert_dir):/certificates --detach --name nextcloud nextcloud:latest
	sleep 5
	docker cp $(build_dir)/$(app_name)-$(version).tar.gz nextcloud:/var/www/html/custom_apps
	docker exec -u www-data nextcloud /bin/bash -c "cd /var/www/html/custom_apps && tar -xzf $(app_name)-$(version).tar.gz && rm $(app_name)-$(version).tar.gz"
	docker exec -u www-data nextcloud /bin/bash -c "php /var/www/html/occ integrity:sign-app --certificate /certificates/$(app_name).crt --privateKey /certificates/$(app_name).key --path /var/www/html/custom_apps/$(app_name)"
	docker exec -u www-data nextcloud /bin/bash -c "cd /var/www/html/custom_apps && tar pzcf $(app_name)-$(version).tar.gz $(app_name)"
	docker cp nextcloud:/var/www/html/custom_apps/$(app_name)-$(version).tar.gz $(build_dir)/$(app_name)-$(version).tar.gz
	sleep 3
	docker kill nextcloud

appstore: sign

clean:
	rm -rf $(build_dir)

package: clean build
	mkdir -p $(sign_dir)
	rsync -a \
	--exclude=/build \
	--exclude=/docs \
	--exclude=/translationfiles \
	--exclude=.tx \
	--exclude=/tests \
	--exclude=.git \
	--exclude=.github \
	--exclude=/l10n/l10n.pl \
	--exclude=/CONTRIBUTING.md \
	--exclude=/issue_template.md \
	--exclude=.gitattributes \
	--exclude=.gitignore \
	--exclude=.scrutinizer.yml \
	--exclude=.travis.yml \
	--exclude=/Makefile \
	--exclude=.drone.yml \
	$(project_dir)/ $(sign_dir)/$(app_name)
	tar -czf $(build_dir)/$(app_name)-$(version).tar.gz \
		-C $(sign_dir) $(app_name)


# Fetches the PHP and JS dependencies and compiles the JS. If no composer.json
# is present, the composer step is skipped, if no package.json or js/package.json
# is present, the npm step is skipped
.PHONY: build
build:
ifneq (,$(wildcard $(project_dir)/composer.json))
	make composer
endif
ifneq (,$(wildcard $(project_dir)/package.json))
	make npm
endif
ifneq (,$(wildcard $(project_dir)/js/package.json))
	make npm
endif

# Installs and updates the composer dependencies. If composer is not installed
# a copy is fetched from the web
.PHONY: composer
composer:
ifeq (, $(composer))
	@echo "No composer command available, downloading a copy from the web"
	mkdir -p $(build_tools_dir)
	curl -sS https://getcomposer.org/installer | php
	mv composer.phar $(build_tools_dir)
	cd $(project_dir) && php $(build_tools_dir)/composer.phar install --prefer-dist
else
	cd $(project_dir) && composer install --prefer-dist
endif

# Installs npm dependencies
.PHONY: npm
npm:
	cd $(project_dir) && npm install
	cd $(project_dir) && npm run build

# Same as clean but also removes dependencies installed by composer, bower and
# npm
.PHONY: distclean
distclean: clean
	rm -rf $(project_dir)/vendor
	rm -rf $(project_dir)/node_modules

.PHONY: test
test: composer
	$(project_dir)/vendor/phpunit/phpunit/phpunit -c $(project_dir)/phpunit.xml
	$(project_dir)/vendor/phpunit/phpunit/phpunit -c $(project_dir)/phpunit.integration.xml
