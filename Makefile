.PHONY: test install update rebuild

default: test

rebuild:
	docker build -t crop .

update:
	docker run -it --rm -v `pwd`:/var/workspace --name crop-running crop composer update

install:
	docker run -it --rm -v `pwd`:/var/workspace --name crop-running crop composer install

test:
	docker run -it --rm -v `pwd`:/var/workspace --name crop-running crop ./vendor/bin/phpspec run
