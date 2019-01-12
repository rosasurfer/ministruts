<?php

// autoload_classmap.php @generated by Composer

$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname(dirname($vendorDir));

return array(
    'rosasurfer\\Application' => $baseDir . '/src/Application.php',
    'rosasurfer\\acl\\ACL' => $baseDir . '/src/acl/ACL.php',
    'rosasurfer\\acl\\AclManager' => $baseDir . '/src/acl/AclManager.php',
    'rosasurfer\\acl\\IAccessControlled' => $baseDir . '/src/acl/IAccessControlled.php',
    'rosasurfer\\acl\\IIdentityNode' => $baseDir . '/src/acl/IIdentityNode.php',
    'rosasurfer\\acl\\IPolicyAware' => $baseDir . '/src/acl/IPolicyAware.php',
    'rosasurfer\\acl\\IRoleAware' => $baseDir . '/src/acl/IRoleAware.php',
    'rosasurfer\\acl\\Policy' => $baseDir . '/src/acl/Policy.php',
    'rosasurfer\\acl\\PolicyInterface' => $baseDir . '/src/acl/PolicyInterface.php',
    'rosasurfer\\acl\\Role' => $baseDir . '/src/acl/Role.php',
    'rosasurfer\\acl\\RoleInterface' => $baseDir . '/src/acl/RoleInterface.php',
    'rosasurfer\\acl\\adapter\\AdapterInterface' => $baseDir . '/src/acl/adapter/AdapterInterface.php',
    'rosasurfer\\acl\\adapter\\ConfigFileAdapter' => $baseDir . '/src/acl/adapter/ConfigFileAdapter.php',
    'rosasurfer\\acl\\adapter\\DbAdapter' => $baseDir . '/src/acl/adapter/DbAdapter.php',
    'rosasurfer\\acl\\adapter\\IniFileAdapter' => $baseDir . '/src/acl/adapter/IniFileAdapter.php',
    'rosasurfer\\cache\\ApcCache' => $baseDir . '/src/cache/ApcCache.php',
    'rosasurfer\\cache\\Cache' => $baseDir . '/src/cache/Cache.php',
    'rosasurfer\\cache\\CachePeer' => $baseDir . '/src/cache/CachePeer.php',
    'rosasurfer\\cache\\FileSystemCache' => $baseDir . '/src/cache/FileSystemCache.php',
    'rosasurfer\\cache\\ReferencePool' => $baseDir . '/src/cache/ReferencePool.php',
    'rosasurfer\\config\\AutoConfig' => $baseDir . '/src/config/AutoConfig.php',
    'rosasurfer\\config\\Config' => $baseDir . '/src/config/Config.php',
    'rosasurfer\\config\\ConfigInterface' => $baseDir . '/src/config/ConfigInterface.php',
    'rosasurfer\\core\\Object' => $baseDir . '/src/core/Object.php',
    'rosasurfer\\core\\ObjectTrait' => $baseDir . '/src/core/ObjectTrait.php',
    'rosasurfer\\core\\Singleton' => $baseDir . '/src/core/Singleton.php',
    'rosasurfer\\core\\StaticClass' => $baseDir . '/src/core/StaticClass.php',
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
    'rosasurfer\\debug\\DebugHelper' => $baseDir . '/src/debug/DebugHelper.php',
    'rosasurfer\\debug\\ErrorHandler' => $baseDir . '/src/debug/ErrorHandler.php',
    'rosasurfer\\di\\Di' => $baseDir . '/src/di/Di.php',
    'rosasurfer\\di\\DiAwareTrait' => $baseDir . '/src/di/DiAwareTrait.php',
    'rosasurfer\\di\\DiInterface' => $baseDir . '/src/di/DiInterface.php',
    'rosasurfer\\di\\def\\DefaultCliDi' => $baseDir . '/src/di/def/DefaultCliDi.php',
    'rosasurfer\\di\\def\\DefaultDi' => $baseDir . '/src/di/def/DefaultDi.php',
    'rosasurfer\\exception\\BusinessRuleException' => $baseDir . '/src/exception/BusinessRuleException.php',
    'rosasurfer\\exception\\ClassNotFoundException' => $baseDir . '/src/exception/ClassNotFoundException.php',
    'rosasurfer\\exception\\ConcurrentModificationException' => $baseDir . '/src/exception/ConcurrentModificationException.php',
    'rosasurfer\\exception\\FileNotFoundException' => $baseDir . '/src/exception/FileNotFoundException.php',
    'rosasurfer\\exception\\IOException' => $baseDir . '/src/exception/IOException.php',
    'rosasurfer\\exception\\IllegalAccessException' => $baseDir . '/src/exception/IllegalAccessException.php',
    'rosasurfer\\exception\\IllegalArgumentException' => $baseDir . '/src/exception/IllegalArgumentException.php',
    'rosasurfer\\exception\\IllegalStateException' => $baseDir . '/src/exception/IllegalStateException.php',
    'rosasurfer\\exception\\IllegalTypeException' => $baseDir . '/src/exception/IllegalTypeException.php',
    'rosasurfer\\exception\\InfrastructureException' => $baseDir . '/src/exception/InfrastructureException.php',
    'rosasurfer\\exception\\InvalidArgumentException' => $baseDir . '/src/exception/InvalidArgumentException.php',
    'rosasurfer\\exception\\PermissionDeniedException' => $baseDir . '/src/exception/PermissionDeniedException.php',
    'rosasurfer\\exception\\RosasurferException' => $baseDir . '/src/exception/RosasurferException.php',
    'rosasurfer\\exception\\RosasurferExceptionInterface' => $baseDir . '/src/exception/RosasurferExceptionInterface.php',
    'rosasurfer\\exception\\RosasurferExceptionTrait' => $baseDir . '/src/exception/RosasurferExceptionTrait.php',
    'rosasurfer\\exception\\RuntimeException' => $baseDir . '/src/exception/RuntimeException.php',
    'rosasurfer\\exception\\UnimplementedFeatureException' => $baseDir . '/src/exception/UnimplementedFeatureException.php',
    'rosasurfer\\exception\\UnsupportedMethodException' => $baseDir . '/src/exception/UnsupportedMethodException.php',
    'rosasurfer\\exception\\error\\PHPCompileError' => $baseDir . '/src/exception/error/PHPCompileError.php',
    'rosasurfer\\exception\\error\\PHPCompileWarning' => $baseDir . '/src/exception/error/PHPCompileWarning.php',
    'rosasurfer\\exception\\error\\PHPCoreError' => $baseDir . '/src/exception/error/PHPCoreError.php',
    'rosasurfer\\exception\\error\\PHPCoreWarning' => $baseDir . '/src/exception/error/PHPCoreWarning.php',
    'rosasurfer\\exception\\error\\PHPDeprecated' => $baseDir . '/src/exception/error/PHPDeprecated.php',
    'rosasurfer\\exception\\error\\PHPError' => $baseDir . '/src/exception/error/PHPError.php',
    'rosasurfer\\exception\\error\\PHPNotice' => $baseDir . '/src/exception/error/PHPNotice.php',
    'rosasurfer\\exception\\error\\PHPParseError' => $baseDir . '/src/exception/error/PHPParseError.php',
    'rosasurfer\\exception\\error\\PHPRecoverableError' => $baseDir . '/src/exception/error/PHPRecoverableError.php',
    'rosasurfer\\exception\\error\\PHPStrict' => $baseDir . '/src/exception/error/PHPStrict.php',
    'rosasurfer\\exception\\error\\PHPUnknownError' => $baseDir . '/src/exception/error/PHPUnknownError.php',
    'rosasurfer\\exception\\error\\PHPUserDeprecated' => $baseDir . '/src/exception/error/PHPUserDeprecated.php',
    'rosasurfer\\exception\\error\\PHPUserError' => $baseDir . '/src/exception/error/PHPUserError.php',
    'rosasurfer\\exception\\error\\PHPUserNotice' => $baseDir . '/src/exception/error/PHPUserNotice.php',
    'rosasurfer\\exception\\error\\PHPUserWarning' => $baseDir . '/src/exception/error/PHPUserWarning.php',
    'rosasurfer\\exception\\error\\PHPWarning' => $baseDir . '/src/exception/error/PHPWarning.php',
    'rosasurfer\\loader\\ClassLoader' => $baseDir . '/src/loader/ClassLoader.php',
    'rosasurfer\\lock\\BaseLock' => $baseDir . '/src/lock/BaseLock.php',
    'rosasurfer\\lock\\FileLock' => $baseDir . '/src/lock/FileLock.php',
    'rosasurfer\\lock\\Lock' => $baseDir . '/src/lock/Lock.php',
    'rosasurfer\\lock\\SystemFiveLock' => $baseDir . '/src/lock/SystemFiveLock.php',
    'rosasurfer\\log\\Logger' => $baseDir . '/src/log/Logger.php',
    'rosasurfer\\ministruts\\Action' => $baseDir . '/src/ministruts/Action.php',
    'rosasurfer\\ministruts\\ActionForm' => $baseDir . '/src/ministruts/ActionForm.php',
    'rosasurfer\\ministruts\\ActionForward' => $baseDir . '/src/ministruts/ActionForward.php',
    'rosasurfer\\ministruts\\ActionMapping' => $baseDir . '/src/ministruts/ActionMapping.php',
    'rosasurfer\\ministruts\\DispatchAction' => $baseDir . '/src/ministruts/DispatchAction.php',
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
    'rosasurfer\\monitor\\ChainedDependency' => $baseDir . '/src/monitor/ChainedDependency.php',
    'rosasurfer\\monitor\\Dependency' => $baseDir . '/src/monitor/Dependency.php',
    'rosasurfer\\monitor\\FileDependency' => $baseDir . '/src/monitor/FileDependency.php',
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
