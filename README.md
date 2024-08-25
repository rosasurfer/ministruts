[![Build Status](https://scrutinizer-ci.com/g/rosasurfer/ministruts/badges/build.png?b=master)](https://scrutinizer-ci.com/g/rosasurfer/ministruts/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/rosasurfer/ministruts/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/rosasurfer/ministruts/?branch=master)


MiniStruts
----------
**MVC** micro framework for PHP inspired by Java Struts.

**M** - The basis of the model component is an ORM. The ORM is implemented as a "Data Mapper" and follows the "database first" approach.
This approach is mainly designed for working with existing databases. The implementation requires neither code generator nor proxy classes
(no surprises from code you didn't write). As long as tables have primary keys, any standard and non-standard database model can be
connected and manually customized. Configuration uses pure PHP (no annotations or attributes).

**V** - The view component is realized by a layout engine which is modelled after the Tiles feature of Java Struts. Pages can be composed
using pre-defined layouts and HTML fragments (a.k.a tiles). Both can be configured and extended as needed. The template language is pure
PHP (no pre-compiling of HTML necessary).

**C** - The controller part covers application framework, configuration, service container, access control, caching, error handling and
logging. The web part operates by the principles of Java Struts.

- - -

MiniStruts reference: [struts-config.dtd](src/struts/dtd/struts-config.dtd)

ORM mapping reference: [README.md](src/db/orm/README.md)
