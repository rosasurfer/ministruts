<?php

// autoload_classmap.php @generated by Composer

$vendorDir = dirname(__DIR__);
$baseDir = dirname($vendorDir);

return array(
    'rosasurfer\\Application' => $baseDir . '/src/Application.php',
    'rosasurfer\\cache\\ApcCache' => $baseDir . '/src/cache/ApcCache.php',
    'rosasurfer\\cache\\Cache' => $baseDir . '/src/cache/Cache.php',
    'rosasurfer\\cache\\CachePeer' => $baseDir . '/src/cache/CachePeer.php',
    'rosasurfer\\cache\\FileSystemCache' => $baseDir . '/src/cache/FileSystemCache.php',
    'rosasurfer\\cache\\ReferencePool' => $baseDir . '/src/cache/ReferencePool.php',
    'rosasurfer\\cache\\monitor\\ChainedDependency' => $baseDir . '/src/cache/monitor/ChainedDependency.php',
    'rosasurfer\\cache\\monitor\\Dependency' => $baseDir . '/src/cache/monitor/Dependency.php',
    'rosasurfer\\cache\\monitor\\FileDependency' => $baseDir . '/src/cache/monitor/FileDependency.php',
    'rosasurfer\\config\\Config' => $baseDir . '/src/config/Config.php',
    'rosasurfer\\config\\ConfigInterface' => $baseDir . '/src/config/ConfigInterface.php',
    'rosasurfer\\config\\defaultt\\DefaultConfig' => $baseDir . '/src/config/defaultt/DefaultConfig.php',
    'rosasurfer\\console\\Command' => $baseDir . '/src/console/Command.php',
    'rosasurfer\\console\\docopt\\DocoptParser' => $baseDir . '/src/console/docopt/DocoptParser.php',
    'rosasurfer\\console\\docopt\\DocoptResult' => $baseDir . '/src/console/docopt/DocoptResult.php',
    'rosasurfer\\console\\docopt\\SingleMatch' => $baseDir . '/src/console/docopt/SingleMatch.php',
    'rosasurfer\\console\\docopt\\TokenIterator' => $baseDir . '/src/console/docopt/TokenIterator.php',
    'rosasurfer\\console\\docopt\\exception\\DocoptFormatError' => $baseDir . '/src/console/docopt/exception/DocoptFormatError.php',
    'rosasurfer\\console\\docopt\\exception\\DocoptUserNotification' => $baseDir . '/src/console/docopt/exception/DocoptUserNotification.php',
    'rosasurfer\\console\\docopt\\pattern\\Argument' => $baseDir . '/src/console/docopt/pattern/Argument.php',
    'rosasurfer\\console\\docopt\\pattern\\BranchPattern' => $baseDir . '/src/console/docopt/pattern/BranchPattern.php',
    'rosasurfer\\console\\docopt\\pattern\\Command' => $baseDir . '/src/console/docopt/pattern/Command.php',
    'rosasurfer\\console\\docopt\\pattern\\Either' => $baseDir . '/src/console/docopt/pattern/Either.php',
    'rosasurfer\\console\\docopt\\pattern\\LeafPattern' => $baseDir . '/src/console/docopt/pattern/LeafPattern.php',
    'rosasurfer\\console\\docopt\\pattern\\OneOrMore' => $baseDir . '/src/console/docopt/pattern/OneOrMore.php',
    'rosasurfer\\console\\docopt\\pattern\\Option' => $baseDir . '/src/console/docopt/pattern/Option.php',
    'rosasurfer\\console\\docopt\\pattern\\Optional' => $baseDir . '/src/console/docopt/pattern/Optional.php',
    'rosasurfer\\console\\docopt\\pattern\\OptionsShortcut' => $baseDir . '/src/console/docopt/pattern/OptionsShortcut.php',
    'rosasurfer\\console\\docopt\\pattern\\Pattern' => $baseDir . '/src/console/docopt/pattern/Pattern.php',
    'rosasurfer\\console\\docopt\\pattern\\Required' => $baseDir . '/src/console/docopt/pattern/Required.php',
    'rosasurfer\\console\\io\\Input' => $baseDir . '/src/console/io/Input.php',
    'rosasurfer\\console\\io\\Output' => $baseDir . '/src/console/io/Output.php',
    'rosasurfer\\core\\CObject' => $baseDir . '/src/core/CObject.php',
    'rosasurfer\\core\\ObjectTrait' => $baseDir . '/src/core/ObjectTrait.php',
    'rosasurfer\\core\\Singleton' => $baseDir . '/src/core/Singleton.php',
    'rosasurfer\\core\\StaticClass' => $baseDir . '/src/core/StaticClass.php',
    'rosasurfer\\core\\assert\\Assert' => $baseDir . '/src/core/assert/Assert.php',
    'rosasurfer\\core\\assert\\FailedAssertionTrait' => $baseDir . '/src/core/assert/FailedAssertionTrait.php',
    'rosasurfer\\core\\assert\\InvalidTypeException' => $baseDir . '/src/core/assert/InvalidTypeException.php',
    'rosasurfer\\core\\assert\\InvalidValueException' => $baseDir . '/src/core/assert/InvalidValueException.php',
    'rosasurfer\\core\\di\\ContainerException' => $baseDir . '/src/core/di/ContainerException.php',
    'rosasurfer\\core\\di\\Di' => $baseDir . '/src/core/di/Di.php',
    'rosasurfer\\core\\di\\DiAwareTrait' => $baseDir . '/src/core/di/DiAwareTrait.php',
    'rosasurfer\\core\\di\\DiInterface' => $baseDir . '/src/core/di/DiInterface.php',
    'rosasurfer\\core\\di\\defaultt\\CliServiceContainer' => $baseDir . '/src/core/di/defaultt/CliServiceContainer.php',
    'rosasurfer\\core\\di\\defaultt\\WebServiceContainer' => $baseDir . '/src/core/di/defaultt/WebServiceContainer.php',
    'rosasurfer\\core\\di\\facade\\Facade' => $baseDir . '/src/core/di/facade/Facade.php',
    'rosasurfer\\core\\di\\facade\\Form' => $baseDir . '/src/core/di/facade/Form.php',
    'rosasurfer\\core\\di\\facade\\Forms' => $baseDir . '/src/core/di/facade/Forms.php',
    'rosasurfer\\core\\di\\facade\\Input' => $baseDir . '/src/core/di/facade/Input.php',
    'rosasurfer\\core\\di\\facade\\Inputs' => $baseDir . '/src/core/di/facade/Inputs.php',
    'rosasurfer\\core\\di\\proxy\\CliInput' => $baseDir . '/src/core/di/proxy/CliInput.php',
    'rosasurfer\\core\\di\\proxy\\Output' => $baseDir . '/src/core/di/proxy/Output.php',
    'rosasurfer\\core\\di\\proxy\\Proxy' => $baseDir . '/src/core/di/proxy/Proxy.php',
    'rosasurfer\\core\\di\\proxy\\Request' => $baseDir . '/src/core/di/proxy/Request.php',
    'rosasurfer\\core\\di\\service\\Service' => $baseDir . '/src/core/di/service/Service.php',
    'rosasurfer\\core\\di\\service\\ServiceInterface' => $baseDir . '/src/core/di/service/ServiceInterface.php',
    'rosasurfer\\core\\di\\service\\ServiceNotFoundException' => $baseDir . '/src/core/di/service/ServiceNotFoundException.php',
    'rosasurfer\\core\\error\\ErrorHandler' => $baseDir . '/src/core/error/ErrorHandler.php',
    'rosasurfer\\core\\error\\PHPCompileError' => $baseDir . '/src/core/error/PHPCompileError.php',
    'rosasurfer\\core\\error\\PHPCompileWarning' => $baseDir . '/src/core/error/PHPCompileWarning.php',
    'rosasurfer\\core\\error\\PHPCoreError' => $baseDir . '/src/core/error/PHPCoreError.php',
    'rosasurfer\\core\\error\\PHPCoreWarning' => $baseDir . '/src/core/error/PHPCoreWarning.php',
    'rosasurfer\\core\\error\\PHPDeprecated' => $baseDir . '/src/core/error/PHPDeprecated.php',
    'rosasurfer\\core\\error\\PHPError' => $baseDir . '/src/core/error/PHPError.php',
    'rosasurfer\\core\\error\\PHPNotice' => $baseDir . '/src/core/error/PHPNotice.php',
    'rosasurfer\\core\\error\\PHPParseError' => $baseDir . '/src/core/error/PHPParseError.php',
    'rosasurfer\\core\\error\\PHPRecoverableError' => $baseDir . '/src/core/error/PHPRecoverableError.php',
    'rosasurfer\\core\\error\\PHPStrict' => $baseDir . '/src/core/error/PHPStrict.php',
    'rosasurfer\\core\\error\\PHPUnknownError' => $baseDir . '/src/core/error/PHPUnknownError.php',
    'rosasurfer\\core\\error\\PHPUserDeprecated' => $baseDir . '/src/core/error/PHPUserDeprecated.php',
    'rosasurfer\\core\\error\\PHPUserError' => $baseDir . '/src/core/error/PHPUserError.php',
    'rosasurfer\\core\\error\\PHPUserNotice' => $baseDir . '/src/core/error/PHPUserNotice.php',
    'rosasurfer\\core\\error\\PHPUserWarning' => $baseDir . '/src/core/error/PHPUserWarning.php',
    'rosasurfer\\core\\error\\PHPWarning' => $baseDir . '/src/core/error/PHPWarning.php',
    'rosasurfer\\core\\exception\\BusinessRuleException' => $baseDir . '/src/core/exception/BusinessRuleException.php',
    'rosasurfer\\core\\exception\\ClassNotFoundException' => $baseDir . '/src/core/exception/ClassNotFoundException.php',
    'rosasurfer\\core\\exception\\ConcurrentModificationException' => $baseDir . '/src/core/exception/ConcurrentModificationException.php',
    'rosasurfer\\core\\exception\\FileNotFoundException' => $baseDir . '/src/core/exception/FileNotFoundException.php',
    'rosasurfer\\core\\exception\\IOException' => $baseDir . '/src/core/exception/IOException.php',
    'rosasurfer\\core\\exception\\IllegalAccessException' => $baseDir . '/src/core/exception/IllegalAccessException.php',
    'rosasurfer\\core\\exception\\IllegalStateException' => $baseDir . '/src/core/exception/IllegalStateException.php',
    'rosasurfer\\core\\exception\\InfrastructureException' => $baseDir . '/src/core/exception/InfrastructureException.php',
    'rosasurfer\\core\\exception\\InvalidTypeException' => $baseDir . '/src/core/exception/InvalidTypeException.php',
    'rosasurfer\\core\\exception\\InvalidValueException' => $baseDir . '/src/core/exception/InvalidValueException.php',
    'rosasurfer\\core\\exception\\NotFoundException' => $baseDir . '/src/core/exception/NotFoundException.php',
    'rosasurfer\\core\\exception\\PermissionDeniedException' => $baseDir . '/src/core/exception/PermissionDeniedException.php',
    'rosasurfer\\core\\exception\\RosasurferException' => $baseDir . '/src/core/exception/RosasurferException.php',
    'rosasurfer\\core\\exception\\RosasurferExceptionInterface' => $baseDir . '/src/core/exception/RosasurferExceptionInterface.php',
    'rosasurfer\\core\\exception\\RosasurferExceptionTrait' => $baseDir . '/src/core/exception/RosasurferExceptionTrait.php',
    'rosasurfer\\core\\exception\\RuntimeException' => $baseDir . '/src/core/exception/RuntimeException.php',
    'rosasurfer\\core\\exception\\UnimplementedFeatureException' => $baseDir . '/src/core/exception/UnimplementedFeatureException.php',
    'rosasurfer\\core\\exception\\UnsupportedMethodException' => $baseDir . '/src/core/exception/UnsupportedMethodException.php',
    'rosasurfer\\core\\loader\\ClassLoader' => $baseDir . '/src/core/loader/ClassLoader.php',
    'rosasurfer\\core\\lock\\BaseLock' => $baseDir . '/src/core/lock/BaseLock.php',
    'rosasurfer\\core\\lock\\FileLock' => $baseDir . '/src/core/lock/FileLock.php',
    'rosasurfer\\core\\lock\\Lock' => $baseDir . '/src/core/lock/Lock.php',
    'rosasurfer\\core\\lock\\SystemFiveLock' => $baseDir . '/src/core/lock/SystemFiveLock.php',
    'rosasurfer\\db\\ConnectionPool' => $baseDir . '/src/db/ConnectionPool.php',
    'rosasurfer\\db\\Connector' => $baseDir . '/src/db/Connector.php',
    'rosasurfer\\db\\ConnectorInterface' => $baseDir . '/src/db/ConnectorInterface.php',
    'rosasurfer\\db\\DatabaseException' => $baseDir . '/src/db/DatabaseException.php',
    'rosasurfer\\db\\MultipleRecordsException' => $baseDir . '/src/db/MultipleRecordsException.php',
    'rosasurfer\\db\\NoMoreRecordsException' => $baseDir . '/src/db/NoMoreRecordsException.php',
    'rosasurfer\\db\\NoSuchRecordException' => $baseDir . '/src/db/NoSuchRecordException.php',
    'rosasurfer\\db\\Result' => $baseDir . '/src/db/Result.php',
    'rosasurfer\\db\\ResultInterface' => $baseDir . '/src/db/ResultInterface.php',
    'rosasurfer\\db\\mysql\\MySQLConnector' => $baseDir . '/src/db/mysql/MySQLConnector.php',
    'rosasurfer\\db\\mysql\\MySQLResult' => $baseDir . '/src/db/mysql/MySQLResult.php',
    'rosasurfer\\db\\orm\\DAO' => $baseDir . '/src/db/orm/DAO.php',
    'rosasurfer\\db\\orm\\PersistableObject' => $baseDir . '/src/db/orm/PersistableObject.php',
    'rosasurfer\\db\\orm\\Worker' => $baseDir . '/src/db/orm/Worker.php',
    'rosasurfer\\db\\orm\\meta\\EntityMapping' => $baseDir . '/src/db/orm/meta/EntityMapping.php',
    'rosasurfer\\db\\orm\\meta\\Model' => $baseDir . '/src/db/orm/meta/Model.php',
    'rosasurfer\\db\\orm\\meta\\PropertyMapping' => $baseDir . '/src/db/orm/meta/PropertyMapping.php',
    'rosasurfer\\db\\orm\\meta\\Type' => $baseDir . '/src/db/orm/meta/Type.php',
    'rosasurfer\\db\\pgsql\\PostgresConnector' => $baseDir . '/src/db/pgsql/PostgresConnector.php',
    'rosasurfer\\db\\pgsql\\PostgresResult' => $baseDir . '/src/db/pgsql/PostgresResult.php',
    'rosasurfer\\db\\sqlite\\SQLiteConnector' => $baseDir . '/src/db/sqlite/SQLiteConnector.php',
    'rosasurfer\\db\\sqlite\\SQLiteResult' => $baseDir . '/src/db/sqlite/SQLiteResult.php',
    'rosasurfer\\file\\FileSystem' => $baseDir . '/src/file/FileSystem.php',
    'rosasurfer\\file\\xml\\SimpleXMLElement' => $baseDir . '/src/file/xml/SimpleXMLElement.php',
    'rosasurfer\\file\\zip\\ZipArchive' => $baseDir . '/src/file/zip/ZipArchive.php',
    'rosasurfer\\log\\Logger' => $baseDir . '/src/log/Logger.php',
    'rosasurfer\\log\\context\\RequestData' => $baseDir . '/src/log/context/RequestData.php',
    'rosasurfer\\ministruts\\Action' => $baseDir . '/src/ministruts/Action.php',
    'rosasurfer\\ministruts\\ActionForm' => $baseDir . '/src/ministruts/ActionForm.php',
    'rosasurfer\\ministruts\\ActionForward' => $baseDir . '/src/ministruts/ActionForward.php',
    'rosasurfer\\ministruts\\ActionInput' => $baseDir . '/src/ministruts/ActionInput.php',
    'rosasurfer\\ministruts\\ActionMapping' => $baseDir . '/src/ministruts/ActionMapping.php',
    'rosasurfer\\ministruts\\DispatchAction' => $baseDir . '/src/ministruts/DispatchAction.php',
    'rosasurfer\\ministruts\\EmptyActionForm' => $baseDir . '/src/ministruts/EmptyActionForm.php',
    'rosasurfer\\ministruts\\FrontController' => $baseDir . '/src/ministruts/FrontController.php',
    'rosasurfer\\ministruts\\HttpSession' => $baseDir . '/src/ministruts/HttpSession.php',
    'rosasurfer\\ministruts\\Module' => $baseDir . '/src/ministruts/Module.php',
    'rosasurfer\\ministruts\\Page' => $baseDir . '/src/ministruts/Page.php',
    'rosasurfer\\ministruts\\Request' => $baseDir . '/src/ministruts/Request.php',
    'rosasurfer\\ministruts\\RequestProcessor' => $baseDir . '/src/ministruts/RequestProcessor.php',
    'rosasurfer\\ministruts\\Response' => $baseDir . '/src/ministruts/Response.php',
    'rosasurfer\\ministruts\\RoleProcessor' => $baseDir . '/src/ministruts/RoleProcessor.php',
    'rosasurfer\\ministruts\\StrutsConfigException' => $baseDir . '/src/ministruts/StrutsConfigException.php',
    'rosasurfer\\ministruts\\Tile' => $baseDir . '/src/ministruts/Tile.php',
    'rosasurfer\\ministruts\\url\\Url' => $baseDir . '/src/ministruts/url/Url.php',
    'rosasurfer\\ministruts\\url\\VersionedUrl' => $baseDir . '/src/ministruts/url/VersionedUrl.php',
    'rosasurfer\\net\\NetTools' => $baseDir . '/src/net/NetTools.php',
    'rosasurfer\\net\\TorHelper' => $baseDir . '/src/net/TorHelper.php',
    'rosasurfer\\net\\http\\CurlHttpClient' => $baseDir . '/src/net/http/CurlHttpClient.php',
    'rosasurfer\\net\\http\\CurlHttpResponse' => $baseDir . '/src/net/http/CurlHttpResponse.php',
    'rosasurfer\\net\\http\\HeaderParser' => $baseDir . '/src/net/http/HeaderParser.php',
    'rosasurfer\\net\\http\\HttpClient' => $baseDir . '/src/net/http/HttpClient.php',
    'rosasurfer\\net\\http\\HttpRequest' => $baseDir . '/src/net/http/HttpRequest.php',
    'rosasurfer\\net\\http\\HttpResponse' => $baseDir . '/src/net/http/HttpResponse.php',
    'rosasurfer\\net\\mail\\CLIMailer' => $baseDir . '/src/net/mail/CLIMailer.php',
    'rosasurfer\\net\\mail\\FileSocketMailer' => $baseDir . '/src/net/mail/FileSocketMailer.php',
    'rosasurfer\\net\\mail\\Mailer' => $baseDir . '/src/net/mail/Mailer.php',
    'rosasurfer\\net\\mail\\MailerInterface' => $baseDir . '/src/net/mail/MailerInterface.php',
    'rosasurfer\\net\\mail\\PHPMailer' => $baseDir . '/src/net/mail/PHPMailer.php',
    'rosasurfer\\net\\mail\\SMTPMailer' => $baseDir . '/src/net/mail/SMTPMailer.php',
    'rosasurfer\\net\\messenger\\Messenger' => $baseDir . '/src/net/messenger/Messenger.php',
    'rosasurfer\\net\\messenger\\im\\IRCMessenger' => $baseDir . '/src/net/messenger/im/IRCMessenger.php',
    'rosasurfer\\net\\messenger\\sms\\ClickatellMessenger' => $baseDir . '/src/net/messenger/sms/ClickatellMessenger.php',
    'rosasurfer\\net\\messenger\\sms\\NexmoMessenger' => $baseDir . '/src/net/messenger/sms/NexmoMessenger.php',
    'rosasurfer\\process\\Process' => $baseDir . '/src/process/Process.php',
    'rosasurfer\\util\\Date' => $baseDir . '/src/util/Date.php',
    'rosasurfer\\util\\DateTime' => $baseDir . '/src/util/DateTime.php',
    'rosasurfer\\util\\Number' => $baseDir . '/src/util/Number.php',
    'rosasurfer\\util\\PHP' => $baseDir . '/src/util/PHP.php',
    'rosasurfer\\util\\Validator' => $baseDir . '/src/util/Validator.php',
    'rosasurfer\\util\\Windows' => $baseDir . '/src/util/Windows.php',
);
