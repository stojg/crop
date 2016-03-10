.PHONY: test install update

default: test

update:
	docker run -it --rm -v `pwd`:/var/workspace --name crop-running crop composer update

install:
	docker run -it --rm -v `pwd`:/var/workspace --name crop-running crop composer install

test:
	docker run -it --rm -v `pwd`:/var/workspace --name crop-running crop ./vendor/bin/phpspec run
