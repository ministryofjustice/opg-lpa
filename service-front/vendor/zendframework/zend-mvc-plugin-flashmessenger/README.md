# zend-mvc-plugin-flashmessenger

[![Build Status](https://secure.travis-ci.org/zendframework/zend-mvc-plugin-flashmessenger.svg?branch=master)](https://secure.travis-ci.org/zendframework/zend-mvc-plugin-flashmessenger)
[![Coverage Status](https://coveralls.io/repos/github/zendframework/zend-mvc-plugin-flashmessenger/badge.svg?branch=master)](https://coveralls.io/github/zendframework/zend-mvc-plugin-flashmessenger?branch=master)

Flash messages [derive from Rails](http://api.rubyonrails.org/classes/ActionDispatch/Flash.html),
and are used to expose messages from one action to the next, after which they
are cleared; a typical use case is with
[Post/Redirect/Get](https://docs.zendframework.com/zend-mvc-plugin-prg), where
they are created in the `POST` handler, and then displayed by the `GET` handler
to indicate success or failure to the end-user.

This component provides a flash messenger controller plugin for
[zend-mvc](https://docs.zendframework.com/zend-mvc/) versions 3.0 and up.

- File issues at https://github.com/zendframework/zend-mvc-plugin-flashmessenger/issues
- Documentation is at https://docs.zendframework.com/zend-mvc-plugin-flashmessenger/
