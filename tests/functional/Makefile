NAME = registry.service.opg.digital/opguk/casperjs
VERSION = latest
DATAVOL := $(shell pwd | sed -e 's/srv/nfsdata/')

default:
	@echo Please use \'make build\' or \'make test example.js\'

build:
	docker build -t $(NAME):$(VERSION) .

test:
ifeq ($(BASE_DOMAIN),)
	$(error BASE_DOMAIN variable not set. No target domain for tests)
endif
ifneq ($(suite),)
	docker run -t -e "BASE_DOMAIN=$(BASE_DOMAIN)" --net=host --rm -v $(DATAVOL):/mnt/test $(NAME):$(VERSION) /mnt/test/start.sh 'tests/${suite}'
else
	$(error suite variable not set. No target suite for tests)
endif

runalltests = docker run $(TTY) -e "BASE_DOMAIN=$(BASE_DOMAIN)" --net=host --rm -v $(DATAVOL):/mnt/test $(NAME):$(VERSION) /mnt/test/start.sh 'tests/'

testall: showinfo
ifeq ($(BASE_DOMAIN),)
	$(error BASE_DOMAIN variable not set. No target domain for tests)
endif
	$(call runalltests)

local: TTY=-it
local: BASE_DOMAIN="lpa-front.local"
local: showinfo
	$(call runalltests)

qa: TTY=-it
qa: BASE_DOMAIN="front2-qa.dev.lpa.opg.digital"
qa: showinfo
	$(call runalltests)

staging: TTY=-it
staging: BASE_DOMAIN="front2-staging04.dev.lpa.opg.digital"
staging: showinfo
	$(call runalltests)

preprod: TTY=-it
preprod: BASE_DOMAIN="front2-preprod.lpa.opg.digital"
preprod: showinfo
	$(call runalltests)

prod: TTY=-it
prod: BASE_DOMAIN="www.lastingpowerofattorney.service.gov.uk"
prod: showinfo
	$(call runalltests)

shell:
ifeq ($(BASE_DOMAIN),)
	@echo WARN:  BASE_DOMAIN variable not set. No target domain for tests
endif
	sed -i -- 's/BASE_DOMAIN_TARGET/$(BASE_DOMAIN)/' config/Bootstrap.js
	docker run --net=host --rm -it -v $(DATAVOL):/mnt/test $(NAME):$(VERSION) /bin/bash

tag:
	git tag -d $(VERSION) 2>&1 > /dev/null
	git tag -d latest 2>&1 > /dev/null
	git tag $(VERSION)
	git tag latest

showinfo:
	@echo =============================
	@echo Domain Target: $(BASE_DOMAIN)
	@echo Data Volume Path: $(DATAVOL)
	@echo =============================
#push:
#	git push --tags origin master -f
