[![Build Status](https://scrutinizer-ci.com/g/rosasurfer/ministruts/badges/build.png?b=master)](https://scrutinizer-ci.com/g/rosasurfer/ministruts/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/rosasurfer/ministruts/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/rosasurfer/ministruts/?branch=master)


MiniStruts
----------
**MVC** micro framework for PHP


**M** - The basis of the model component is an ORM. The ORM is implemented as a "Data Mapper" and follows the "database first" approach.
This approach is mainly designed for working with existing databases. The implementation requires neither code generation nor proxy classes
(no surprises from code you didn't write). As long as tables have primary keys, any standard and non-standard database model can be connected
and manually customized. Configuration uses pure PHP (no annotations or attributes). The ORM - especially the flexible configuration - was 
inspired by [Hibernate](https://hibernate.org/).


**V** - The view component is realized by a layout engine which was inspired by the [Tiles plugin](https://struts.apache.org/plugins/tiles/)
for Java Struts. Pages can be composed using re-usable layouts and HTML fragments (a.k.a tiles). Both can be configured and extended as needed.
The template language is pure PHP (no pre-compiling of HTML necessary).


**C** - The controller part covers application framework, configuration, service container, access control, caching, error handling and
logging. The web stack of the framework was inspired by [Java Struts 1](https://struts.apache.org/). Version 1 had long been superseded by 
its successor and practically was no longer used. But in 2022 developers decided to revive it due to its simplicity and solidity, and to 
update it to current web standards. [Long live Struts](https://weblegacy.github.io/struts1/)...

---
The project contains a special feature for command line interfaces that's worth mentioning: a syntax parser for the [DocOpt](http://docopt.org/) 
standard which is widely used in the Linux world but little known in the PHP universe. The DocOpt standard uses the syntax description of the 
tool itself to define call options. At runtime syntax descriptions are parsed and validated against the current call. The result are 
beautiful syntax definitions even for complex tools (no more ugly Symfony error messages). Examples are [here](src/console/docopt/examples/git),
you can try out the parser online [here](http://try.docopt.org/).

---
MiniStruts reference: [struts-config.dtd](src/struts/dtd/struts-config.dtd)

ORM mapping reference: [README.md](src/db/orm/README.md)
