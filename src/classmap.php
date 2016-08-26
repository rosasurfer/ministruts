<?php
use const rosasurfer\MINISTRUTS_ROOT;


/**
 * Class map for class loader (fastest way to load classes)
 */
return array(
   'rosasurfer\acl\ACL'                                          => MINISTRUTS_ROOT.'/src/rosasurfer/acl/ACL',
   'rosasurfer\acl\AclManager'                                   => MINISTRUTS_ROOT.'/src/rosasurfer/acl/AclManager',
   'rosasurfer\acl\IAccessControlled'                            => MINISTRUTS_ROOT.'/src/rosasurfer/acl/IAccessControlled',
   'rosasurfer\acl\IIdentityNode'                                => MINISTRUTS_ROOT.'/src/rosasurfer/acl/IIdentityNode',
   'rosasurfer\acl\IPolicyAware'                                 => MINISTRUTS_ROOT.'/src/rosasurfer/acl/IPolicyAware',
   'rosasurfer\acl\IRoleAware'                                   => MINISTRUTS_ROOT.'/src/rosasurfer/acl/IRoleAware',
   'rosasurfer\acl\Policy'                                       => MINISTRUTS_ROOT.'/src/rosasurfer/acl/Policy',
   'rosasurfer\acl\PolicyInterface'                              => MINISTRUTS_ROOT.'/src/rosasurfer/acl/PolicyInterface',
   'rosasurfer\acl\Role'                                         => MINISTRUTS_ROOT.'/src/rosasurfer/acl/Role',
   'rosasurfer\acl\RoleInterface'                                => MINISTRUTS_ROOT.'/src/rosasurfer/acl/RoleInterface',
   'rosasurfer\acl\adapter\AdapterInterface'                     => MINISTRUTS_ROOT.'/src/rosasurfer/acl/adapter/AdapterInterface',
   'rosasurfer\acl\adapter\ConfigFileAdapter'                    => MINISTRUTS_ROOT.'/src/rosasurfer/acl/adapter/ConfigFileAdapter',
   'rosasurfer\acl\adapter\DbAdapter'                            => MINISTRUTS_ROOT.'/src/rosasurfer/acl/adapter/DbAdapter',
   'rosasurfer\acl\adapter\IniFileAdapter'                       => MINISTRUTS_ROOT.'/src/rosasurfer/acl/adapter/IniFileAdapter',

   'ApcCache'                                                    => MINISTRUTS_ROOT.'/src/rosasurfer/cache/ApcCache',
   'Cache'                                                       => MINISTRUTS_ROOT.'/src/rosasurfer/cache/Cache',
   'CachePeer'                                                   => MINISTRUTS_ROOT.'/src/rosasurfer/cache/CachePeer',
   'FileSystemCache'                                             => MINISTRUTS_ROOT.'/src/rosasurfer/cache/FileSystemCache',
   'ReferencePool'                                               => MINISTRUTS_ROOT.'/src/rosasurfer/cache/ReferencePool',

   'Config'                                                      => MINISTRUTS_ROOT.'/src/rosasurfer/config/Config',
   'ConfigInterface'                                             => MINISTRUTS_ROOT.'/src/rosasurfer/config/ConfigInterface',

   'rosasurfer\core\Object'                                      => MINISTRUTS_ROOT.'/src/rosasurfer/core/Object',
   'rosasurfer\core\Singleton'                                   => MINISTRUTS_ROOT.'/src/rosasurfer/core/Singleton',
   'rosasurfer\core\StaticClass'                                 => MINISTRUTS_ROOT.'/src/rosasurfer/core/StaticClass',

   'CommonDAO'                                                   => MINISTRUTS_ROOT.'/src/rosasurfer/dao/CommonDAO',
   'DaoWorker'                                                   => MINISTRUTS_ROOT.'/src/rosasurfer/dao/DaoWorker',
   'PersistableObject'                                           => MINISTRUTS_ROOT.'/src/rosasurfer/dao/PersistableObject',

   'DB'                                                          => MINISTRUTS_ROOT.'/src/rosasurfer/db/DB',
   'DBPool'                                                      => MINISTRUTS_ROOT.'/src/rosasurfer/db/DBPool',
   'MySQLConnector'                                              => MINISTRUTS_ROOT.'/src/rosasurfer/db/MySQLConnector',

   'ChainedDependency'                                           => MINISTRUTS_ROOT.'/src/rosasurfer/dependency/ChainedDependency',
   'Dependency'                                                  => MINISTRUTS_ROOT.'/src/rosasurfer/dependency/Dependency',
   'FileDependency'                                              => MINISTRUTS_ROOT.'/src/rosasurfer/dependency/FileDependency',

   'rosasurfer\exception\BaseException'                          => MINISTRUTS_ROOT.'/src/rosasurfer/exception/BaseException',
   'rosasurfer\exception\BusinessRuleException'                  => MINISTRUTS_ROOT.'/src/rosasurfer/exception/BusinessRuleException',
   'rosasurfer\exception\ClassNotFoundException'                 => MINISTRUTS_ROOT.'/src/rosasurfer/exception/ClassNotFoundException',
   'rosasurfer\exception\ConcurrentModificationException'        => MINISTRUTS_ROOT.'/src/rosasurfer/exception/ConcurrentModificationException',
   'rosasurfer\exception\DatabaseException'                      => MINISTRUTS_ROOT.'/src/rosasurfer/exception/DatabaseException',
   'rosasurfer\exception\FileNotFoundException'                  => MINISTRUTS_ROOT.'/src/rosasurfer/exception/FileNotFoundException',
   'rosasurfer\exception\IOException'                            => MINISTRUTS_ROOT.'/src/rosasurfer/exception/IOException',
   'rosasurfer\exception\IRosasurferException'                   => MINISTRUTS_ROOT.'/src/rosasurfer/exception/IRosasurferException',
   'rosasurfer\exception\IllegalAccessException'                 => MINISTRUTS_ROOT.'/src/rosasurfer/exception/IllegalAccessException',
   'rosasurfer\exception\IllegalArgumentException'               => MINISTRUTS_ROOT.'/src/rosasurfer/exception/IllegalArgumentException',
   'rosasurfer\exception\IllegalStateException'                  => MINISTRUTS_ROOT.'/src/rosasurfer/exception/IllegalStateException',
   'rosasurfer\exception\IllegalTypeException'                   => MINISTRUTS_ROOT.'/src/rosasurfer/exception/IllegalTypeException',
   'rosasurfer\exception\InfrastructureException'                => MINISTRUTS_ROOT.'/src/rosasurfer/exception/InfrastructureException',
   'rosasurfer\exception\InvalidArgumentException'               => MINISTRUTS_ROOT.'/src/rosasurfer/exception/InvalidArgumentException',
   'rosasurfer\exception\PHPError'                               => MINISTRUTS_ROOT.'/src/rosasurfer/exception/PHPError',
   'rosasurfer\exception\PermissionDeniedException'              => MINISTRUTS_ROOT.'/src/rosasurfer/exception/PermissionDeniedException',
   'rosasurfer\exception\RuntimeException'                       => MINISTRUTS_ROOT.'/src/rosasurfer/exception/RuntimeException',
   'rosasurfer\exception\TRosasurferException'                   => MINISTRUTS_ROOT.'/src/rosasurfer/exception/TRosasurferException',
   'rosasurfer\exception\UnimplementedFeatureException'          => MINISTRUTS_ROOT.'/src/rosasurfer/exception/UnimplementedFeatureException',
   'rosasurfer\exception\UnsupportedMethodException'             => MINISTRUTS_ROOT.'/src/rosasurfer/exception/UnsupportedMethodException',

   'BarCode'                                                     => MINISTRUTS_ROOT.'/src/rosasurfer/file/image/barcode/BarCode',
   'BaseC128BarCode'                                             => MINISTRUTS_ROOT.'/src/rosasurfer/file/image/barcode/BaseC128BarCode',
   'C128ABarCode'                                                => MINISTRUTS_ROOT.'/src/rosasurfer/file/image/barcode/C128ABarCode',
   'C128BBarCode'                                                => MINISTRUTS_ROOT.'/src/rosasurfer/file/image/barcode/C128BBarCode',
   'C128CBarCode'                                                => MINISTRUTS_ROOT.'/src/rosasurfer/file/image/barcode/C128CBarCode',
   'C39BarCode'                                                  => MINISTRUTS_ROOT.'/src/rosasurfer/file/image/barcode/C39BarCode',
   'I25BarCode'                                                  => MINISTRUTS_ROOT.'/src/rosasurfer/file/image/barcode/I25BarCode',

   'BasePdfDocument'                                             => MINISTRUTS_ROOT.'/src/rosasurfer/file/pdf/BasePdfDocument',
   'SimplePdfDocument'                                           => MINISTRUTS_ROOT.'/src/rosasurfer/file/pdf/SimplePdfDocument',

   'BaseLock'                                                    => MINISTRUTS_ROOT.'/src/rosasurfer/lock/BaseLock',
   'FileLock'                                                    => MINISTRUTS_ROOT.'/src/rosasurfer/lock/FileLock',
   'Lock'                                                        => MINISTRUTS_ROOT.'/src/rosasurfer/lock/Lock',
   'SystemFiveLock'                                              => MINISTRUTS_ROOT.'/src/rosasurfer/lock/SystemFiveLock',

   'NetTools'                                                    => MINISTRUTS_ROOT.'/src/rosasurfer/net/NetTools',
   'TorHelper'                                                   => MINISTRUTS_ROOT.'/src/rosasurfer/net/TorHelper',

   'CurlHttpClient'                                              => MINISTRUTS_ROOT.'/src/rosasurfer/net/http/CurlHttpClient',
   'CurlHttpResponse'                                            => MINISTRUTS_ROOT.'/src/rosasurfer/net/http/CurlHttpResponse',
   'HeaderParser'                                                => MINISTRUTS_ROOT.'/src/rosasurfer/net/http/HeaderParser',
   'HeaderUtils'                                                 => MINISTRUTS_ROOT.'/src/rosasurfer/net/http/HeaderUtils',
   'HttpClient'                                                  => MINISTRUTS_ROOT.'/src/rosasurfer/net/http/HttpClient',
   'HttpRequest'                                                 => MINISTRUTS_ROOT.'/src/rosasurfer/net/http/HttpRequest',
   'HttpResponse'                                                => MINISTRUTS_ROOT.'/src/rosasurfer/net/http/HttpResponse',

   'CLIMailer'                                                   => MINISTRUTS_ROOT.'/src/rosasurfer/net/mail/CLIMailer',
   'FileSocketMailer'                                            => MINISTRUTS_ROOT.'/src/rosasurfer/net/mail/FileSocketMailer',
   'Mailer'                                                      => MINISTRUTS_ROOT.'/src/rosasurfer/net/mail/Mailer',
   'PHPMailer'                                                   => MINISTRUTS_ROOT.'/src/rosasurfer/net/mail/PHPMailer',
   'SMTPMailer'                                                  => MINISTRUTS_ROOT.'/src/rosasurfer/net/mail/SMTPMailer',

   'rosasurfer\net\messenger\Messenger'                          => MINISTRUTS_ROOT.'/src/rosasurfer/net/messenger/Messenger',
   'rosasurfer\net\messenger\icq\ICQMessenger'                   => MINISTRUTS_ROOT.'/src/rosasurfer/net/messenger/icq/ICQMessenger',
   'rosasurfer\net\messenger\irc\IRCMessenger'                   => MINISTRUTS_ROOT.'/src/rosasurfer/net/messenger/irc/IRCMessenger',
   'rosasurfer\net\messenger\sms\clickatell\ClickatellMessenger' => MINISTRUTS_ROOT.'/src/rosasurfer/net/messenger/sms/clickatell/ClickatellMessenger',
   'rosasurfer\net\messenger\sms\nexmo\NexmoMessenger'           => MINISTRUTS_ROOT.'/src/rosasurfer/net/messenger/sms/nexmo/NexmoMessenger',

   'Action'                                                      => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/Action',
   'ActionForm'                                                  => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/ActionForm',
   'ActionForward'                                               => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/ActionForward',
   'ActionMapping'                                               => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/ActionMapping',
   'HttpSession'                                                 => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/HttpSession',
   'Module'                                                      => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/Module',
   'PageContext'                                                 => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/PageContext',
   'rosasurfer\ministruts\Request'                               => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/Request',
   'RequestProcessor'                                            => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/RequestProcessor',
   'Response'                                                    => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/Response',
   'RoleProcessor'                                               => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/RoleProcessor',
   'rosasurfer\ministruts\StrutsController'                      => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/StrutsController',
   'Tile'                                                        => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/Tile',
   'rosasurfer\ministruts\url\Url'                               => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/url/Url',

   'Date'                                                        => MINISTRUTS_ROOT.'/src/rosasurfer/util/Date',
   'DebugTools'                                                  => MINISTRUTS_ROOT.'/src/rosasurfer/util/DebugTools',
   'Logger'                                                      => MINISTRUTS_ROOT.'/src/rosasurfer/util/Logger',
   'System'                                                      => MINISTRUTS_ROOT.'/src/rosasurfer/util/System',
   'rosasurfer\util\Validator'                                   => MINISTRUTS_ROOT.'/src/rosasurfer/util/Validator',
);
