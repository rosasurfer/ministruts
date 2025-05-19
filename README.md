[![Build Status](https://scrutinizer-ci.com/g/rosasurfer/ministruts/badges/build.png?b=master#)](https://scrutinizer-ci.com/g/rosasurfer/ministruts/build-status/master#)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/rosasurfer/ministruts/badges/quality-score.png?b=master#)](https://scrutinizer-ci.com/g/rosasurfer/ministruts/?branch=master#)
[![PHPStan Level](https://img.shields.io/badge/dynamic/yaml?url=https%3A%2F%2Fraw.githubusercontent.com%2Frosasurfer%2Fministruts%2Frefs%2Fheads%2Fmaster%2Fetc%2Fphpstan%2Fphpstan.dist.neon&query=%24.parameters.level&prefix=Level%20&style=flat&logo=GitHub&label=PHPStan&color=%234cc61e#)](https://github.com/rosasurfer/ministruts/actions?query=branch%3Amaster#)


MiniStruts
----------
**MVC** micro framework for PHP inspired by Java Struts


**M** - The basis of the model component is an ORM. The ORM is implemented as "DataMapper" and follows the "database first" approach.
This approach is primarily intended for working with existing databases. The implementation requires neither code generation nor proxy
classes (no surprises from code you didn't write). As long as tables have primary keys, any standard or non-standard db model can be
connected and customized. The configuration is done in pure PHP (no annotations or attributes).
The ORM is inspired by [Hibernate](https://hibernate.org/#).


**V** - The view component is realized by a layout engine which is inspired by the Struts [Tiles plugin](https://struts.apache.org/plugins/tiles/#).
Pages can be composed using re-usable layouts and HTML fragments. Both can be configured and extended as needed.
Template language is pure PHP (no pre-compiling of HTML necessary).


**V** - The view component is realized by a layout engine inspired by the [Struts Tiles](https://struts.apache.org/plugins/tiles/#) plugin.
Pages can be assembled from reusable layouts and HTML fragments. Both can be configured and extended as needed.
The template language is pure PHP (no pre-compilation of HTML necessary).


**C** - The controller part is comprised of application framework, configuration, service container, access control, caching, error handling
and logging. The web stack of the framework was inspired by [Java Struts 1](https://struts.apache.org/#). Version 1 had long since been
superseded by its successor and was practically no longer used. But in 2022 developers decided to revive it because of its simplicity and
solidity, and to adapt it to current web standards. [Long live Struts](https://weblegacy.github.io/struts1/#)...

---
The project includes a special feature for command line interfaces that is worth mentioning: a syntax parser for the [DocOpt](http://docopt.org/#)
standard, which is well known in the Linux/Python world, but little known in the PHP universe. The DocOpt standard uses the syntax description
of the tool itself to define call options. At runtime, the syntax description is parsed and validated against the current call. The result
are beautiful syntax definitions even for more complex tools (no more ugly Symfony error messages).
Examples are [here](src/console/docopt/examples/git), or you can try out the online parser [here](http://try.docopt.org/#).

---
Mini-Struts reference: [struts-config.dtd](src/struts/dtd/struts-config.dtd)

ORM mapping reference: [README.md](src/db/orm/README.md)
