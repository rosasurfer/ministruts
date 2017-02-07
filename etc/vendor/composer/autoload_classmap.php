<?php

// autoload_classmap.php @generated by Composer

$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname(dirname($vendorDir));

return array(
    'rosasurfer\\MiniStruts' => $baseDir . '/src/MiniStruts.php',
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
    'rosasurfer\\config\\Config' => $baseDir . '/src/config/Config.php',
    'rosasurfer\\config\\ConfigInterface' => $baseDir . '/src/config/ConfigInterface.php',
    'rosasurfer\\config\\StdConfig' => $baseDir . '/src/config/StdConfig.php',
    'rosasurfer\\core\\Object' => $baseDir . '/src/core/Object.php',
    'rosasurfer\\core\\Singleton' => $baseDir . '/src/core/Singleton.php',
    'rosasurfer\\core\\StaticClass' => $baseDir . '/src/core/StaticClass.php',
    'rosasurfer\\db\\ConnectionPool' => $baseDir . '/src/db/ConnectionPool.php',
    'rosasurfer\\db\\Connector' => $baseDir . '/src/db/Connector.php',
    'rosasurfer\\db\\DatabaseException' => $baseDir . '/src/db/DatabaseException.php',
    'rosasurfer\\db\\NoMoreRowsException' => $baseDir . '/src/db/NoMoreRowsException.php',
    'rosasurfer\\db\\Result' => $baseDir . '/src/db/Result.php',
    'rosasurfer\\db\\mysql\\MysqlConnector' => $baseDir . '/src/db/mysql/MysqlConnector.php',
    'rosasurfer\\db\\mysql\\MysqlResult' => $baseDir . '/src/db/mysql/MysqlResult.php',
    'rosasurfer\\db\\orm\\Dao' => $baseDir . '/src/db/orm/Dao.php',
    'rosasurfer\\db\\orm\\PersistableObject' => $baseDir . '/src/db/orm/PersistableObject.php',
    'rosasurfer\\db\\orm\\PersistableSet' => $baseDir . '/src/db/orm/PersistableSet.php',
    'rosasurfer\\db\\orm\\Worker' => $baseDir . '/src/db/orm/Worker.php',
    'rosasurfer\\db\\pgsql\\PostgresConnector' => $baseDir . '/src/db/pgsql/PostgresConnector.php',
    'rosasurfer\\db\\pgsql\\PostgresResult' => $baseDir . '/src/db/pgsql/PostgresResult.php',
    'rosasurfer\\db\\sqlite\\SqliteConnector' => $baseDir . '/src/db/sqlite/SqliteConnector.php',
    'rosasurfer\\db\\sqlite\\SqliteResult' => $baseDir . '/src/db/sqlite/SqliteResult.php',
    'rosasurfer\\debug\\DebugHelper' => $baseDir . '/src/debug/DebugHelper.php',
    'rosasurfer\\debug\\ErrorHandler' => $baseDir . '/src/debug/ErrorHandler.php',
    'rosasurfer\\dependency\\ChainedDependency' => $baseDir . '/src/dependency/ChainedDependency.php',
    'rosasurfer\\dependency\\Dependency' => $baseDir . '/src/dependency/Dependency.php',
    'rosasurfer\\dependency\\FileDependency' => $baseDir . '/src/dependency/FileDependency.php',
    'rosasurfer\\exception\\BaseException' => $baseDir . '/src/exception/BaseException.php',
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
    'rosasurfer\\exception\\RosasurferExceptionInterface' => $baseDir . '/src/exception/RosasurferExceptionInterface.php',
    'rosasurfer\\exception\\RosasurferExceptionTrait' => $baseDir . '/src/exception/RosasurferExceptionTrait.php',
    'rosasurfer\\exception\\RuntimeException' => $baseDir . '/src/exception/RuntimeException.php',
    'rosasurfer\\exception\\UnimplementedFeatureException' => $baseDir . '/src/exception/UnimplementedFeatureException.php',
    'rosasurfer\\exception\\UnsupportedMethodException' => $baseDir . '/src/exception/UnsupportedMethodException.php',
    'rosasurfer\\exception\\php\\PHPCompileError' => $baseDir . '/src/exception/php/PHPCompileError.php',
    'rosasurfer\\exception\\php\\PHPCompileWarning' => $baseDir . '/src/exception/php/PHPCompileWarning.php',
    'rosasurfer\\exception\\php\\PHPCoreError' => $baseDir . '/src/exception/php/PHPCoreError.php',
    'rosasurfer\\exception\\php\\PHPCoreWarning' => $baseDir . '/src/exception/php/PHPCoreWarning.php',
    'rosasurfer\\exception\\php\\PHPDeprecation' => $baseDir . '/src/exception/php/PHPDeprecation.php',
    'rosasurfer\\exception\\php\\PHPError' => $baseDir . '/src/exception/php/PHPError.php',
    'rosasurfer\\exception\\php\\PHPNotice' => $baseDir . '/src/exception/php/PHPNotice.php',
    'rosasurfer\\exception\\php\\PHPParseError' => $baseDir . '/src/exception/php/PHPParseError.php',
    'rosasurfer\\exception\\php\\PHPRecoverableError' => $baseDir . '/src/exception/php/PHPRecoverableError.php',
    'rosasurfer\\exception\\php\\PHPStrictError' => $baseDir . '/src/exception/php/PHPStrictError.php',
    'rosasurfer\\exception\\php\\PHPUnknownError' => $baseDir . '/src/exception/php/PHPUnknownError.php',
    'rosasurfer\\exception\\php\\PHPUserDeprecation' => $baseDir . '/src/exception/php/PHPUserDeprecation.php',
    'rosasurfer\\exception\\php\\PHPUserError' => $baseDir . '/src/exception/php/PHPUserError.php',
    'rosasurfer\\exception\\php\\PHPUserNotice' => $baseDir . '/src/exception/php/PHPUserNotice.php',
    'rosasurfer\\exception\\php\\PHPUserWarning' => $baseDir . '/src/exception/php/PHPUserWarning.php',
    'rosasurfer\\exception\\php\\PHPWarning' => $baseDir . '/src/exception/php/PHPWarning.php',
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
    'rosasurfer\\ministruts\\HttpSession' => $baseDir . '/src/ministruts/HttpSession.php',
    'rosasurfer\\ministruts\\Module' => $baseDir . '/src/ministruts/Module.php',
    'rosasurfer\\ministruts\\PageContext' => $baseDir . '/src/ministruts/PageContext.php',
    'rosasurfer\\ministruts\\Request' => $baseDir . '/src/ministruts/Request.php',
    'rosasurfer\\ministruts\\RequestProcessor' => $baseDir . '/src/ministruts/RequestProcessor.php',
    'rosasurfer\\ministruts\\Response' => $baseDir . '/src/ministruts/Response.php',
    'rosasurfer\\ministruts\\RoleProcessor' => $baseDir . '/src/ministruts/RoleProcessor.php',
    'rosasurfer\\ministruts\\StrutsController' => $baseDir . '/src/ministruts/StrutsController.php',
    'rosasurfer\\ministruts\\Tile' => $baseDir . '/src/ministruts/Tile.php',
    'rosasurfer\\ministruts\\url\\Url' => $baseDir . '/src/ministruts/url/Url.php',
    'rosasurfer\\ministruts\\url\\VersionedUrl' => $baseDir . '/src/ministruts/url/VersionedUrl.php',
    'rosasurfer\\net\\NetTools' => $baseDir . '/src/net/NetTools.php',
    'rosasurfer\\net\\TorHelper' => $baseDir . '/src/net/TorHelper.php',
    'rosasurfer\\net\\http\\CurlHttpClient' => $baseDir . '/src/net/http/CurlHttpClient.php',
    'rosasurfer\\net\\http\\CurlHttpResponse' => $baseDir . '/src/net/http/CurlHttpResponse.php',
    'rosasurfer\\net\\http\\HeaderParser' => $baseDir . '/src/net/http/HeaderParser.php',
    'rosasurfer\\net\\http\\HeaderUtils' => $baseDir . '/src/net/http/HeaderUtils.php',
    'rosasurfer\\net\\http\\HttpClient' => $baseDir . '/src/net/http/HttpClient.php',
    'rosasurfer\\net\\http\\HttpRequest' => $baseDir . '/src/net/http/HttpRequest.php',
    'rosasurfer\\net\\http\\HttpResponse' => $baseDir . '/src/net/http/HttpResponse.php',
    'rosasurfer\\net\\mail\\CLIMailer' => $baseDir . '/src/net/mail/CLIMailer.php',
    'rosasurfer\\net\\mail\\FileSocketMailer' => $baseDir . '/src/net/mail/FileSocketMailer.php',
    'rosasurfer\\net\\mail\\Mailer' => $baseDir . '/src/net/mail/Mailer.php',
    'rosasurfer\\net\\mail\\PHPMailer' => $baseDir . '/src/net/mail/PHPMailer.php',
    'rosasurfer\\net\\mail\\SMTPMailer' => $baseDir . '/src/net/mail/SMTPMailer.php',
    'rosasurfer\\net\\messenger\\Messenger' => $baseDir . '/src/net/messenger/Messenger.php',
    'rosasurfer\\net\\messenger\\im\\IRCMessenger' => $baseDir . '/src/net/messenger/im/IRCMessenger.php',
    'rosasurfer\\net\\messenger\\sms\\ClickatellMessenger' => $baseDir . '/src/net/messenger/sms/ClickatellMessenger.php',
    'rosasurfer\\net\\messenger\\sms\\NexmoMessenger' => $baseDir . '/src/net/messenger/sms/NexmoMessenger.php',
    'rosasurfer\\util\\Date' => $baseDir . '/src/util/Date.php',
    'rosasurfer\\util\\Number' => $baseDir . '/src/util/Number.php',
    'rosasurfer\\util\\PHP' => $baseDir . '/src/util/PHP.php',
    'rosasurfer\\util\\Validator' => $baseDir . '/src/util/Validator.php',
    'rosasurfer\\util\\Windows' => $baseDir . '/src/util/Windows.php',
);
