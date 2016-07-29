<?php
use const rosasurfer\MINISTRUTS_ROOT;


/**
 * Class map for class loader (fastest way to load classes)
 */
return array(
   'rosasurfer\ministruts\acl\ACL'                                    => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/acl/ACL',
   'rosasurfer\ministruts\acl\AclManager'                             => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/acl/AclManager',
   'rosasurfer\ministruts\acl\IAccessControlled'                      => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/acl/IAccessControlled',
   'rosasurfer\ministruts\acl\IIdentityNode'                          => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/acl/IIdentityNode',
   'rosasurfer\ministruts\acl\IPolicyAware'                           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/acl/IPolicyAware',
   'rosasurfer\ministruts\acl\IRoleAware'                             => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/acl/IRoleAware',
   'rosasurfer\ministruts\acl\Policy'                                 => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/acl/Policy',
   'rosasurfer\ministruts\acl\PolicyInterface'                        => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/acl/PolicyInterface',
   'rosasurfer\ministruts\acl\Role'                                   => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/acl/Role',
   'rosasurfer\ministruts\acl\RoleInterface'                          => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/acl/RoleInterface',
   'rosasurfer\ministruts\acl\adapter\AdapterInterface'               => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/acl/adapter/AdapterInterface',
   'rosasurfer\ministruts\acl\adapter\ConfigFileAdapter'              => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/acl/adapter/ConfigFileAdapter',
   'rosasurfer\ministruts\acl\adapter\DbAdapter'                      => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/acl/adapter/DbAdapter',
   'rosasurfer\ministruts\acl\adapter\IniFileAdapter'                 => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/acl/adapter/IniFileAdapter',

   'ApcCache'                                                         => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/cache/ApcCache',
   'Cache'                                                            => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/cache/Cache',
   'CachePeer'                                                        => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/cache/CachePeer',
   'FileSystemCache'                                                  => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/cache/FileSystemCache',
   'ReferencePool'                                                    => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/cache/ReferencePool',

   'Config'                                                           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/config/Config',
   'ConfigInterface'                                                  => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/config/ConfigInterface',

   'rosasurfer\ministruts\core\Object'                                => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/core/Object',
   'rosasurfer\ministruts\core\Singleton'                             => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/core/Singleton',
   'rosasurfer\ministruts\core\StaticClass'                           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/core/StaticClass',

   'CommonDAO'                                                        => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/dao/CommonDAO',
   'DaoWorker'                                                        => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/dao/DaoWorker',
   'PersistableObject'                                                => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/dao/PersistableObject',

   'DB'                                                               => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/db/DB',
   'DBPool'                                                           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/db/DBPool',
   'MySQLConnector'                                                   => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/db/MySQLConnector',

   'ChainedDependency'                                                => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/dependency/ChainedDependency',
   'Dependency'                                                       => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/dependency/Dependency',
   'FileDependency'                                                   => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/dependency/FileDependency',

   'rosasurfer\ministruts\exception\BaseException'                   => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exception/BaseException',
   'rosasurfer\ministruts\exception\BusinessRuleException'           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exception/BusinessRuleException',
   'rosasurfer\ministruts\exception\ClassNotFoundException'          => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exception/ClassNotFoundException',
   'rosasurfer\ministruts\exception\ConcurrentModificationException' => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exception/ConcurrentModificationException',
   'rosasurfer\ministruts\exception\DatabaseException'               => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exception/DatabaseException',
   'rosasurfer\ministruts\exception\FileNotFoundException'           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exception/FileNotFoundException',
   'rosasurfer\ministruts\exception\IOException'                     => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exception/IOException',
   'rosasurfer\ministruts\exception\IRosasurferException'            => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exception/IRosasurferException',
   'rosasurfer\ministruts\exception\IllegalAccessException'          => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exception/IllegalAccessException',
   'rosasurfer\ministruts\exception\IllegalArgumentException'        => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exception/IllegalArgumentException',
   'rosasurfer\ministruts\exception\IllegalStateException'           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exception/IllegalStateException',
   'rosasurfer\ministruts\exception\IllegalTypeException'            => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exception/IllegalTypeException',
   'rosasurfer\ministruts\exception\InfrastructureException'         => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exception/InfrastructureException',
   'rosasurfer\ministruts\exception\InvalidArgumentException'        => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exception/InvalidArgumentException',
   'rosasurfer\ministruts\exception\PHPError'                        => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exception/PHPError',
   'rosasurfer\ministruts\exception\PermissionDeniedException'       => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exception/PermissionDeniedException',
   'rosasurfer\ministruts\exception\RuntimeException'                => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exception/RuntimeException',
   'rosasurfer\ministruts\exception\TRosasurferException'            => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exception/TRosasurferException',
   'rosasurfer\ministruts\exception\UnimplementedFeatureException'   => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exception/UnimplementedFeatureException',
   'rosasurfer\ministruts\exception\UnsupportedMethodException'      => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exception/UnsupportedMethodException',

   'BarCode'                                                          => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/file/image/barcode/BarCode',
   'BaseC128BarCode'                                                  => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/file/image/barcode/BaseC128BarCode',
   'C128ABarCode'                                                     => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/file/image/barcode/C128ABarCode',
   'C128BBarCode'                                                     => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/file/image/barcode/C128BBarCode',
   'C128CBarCode'                                                     => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/file/image/barcode/C128CBarCode',
   'C39BarCode'                                                       => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/file/image/barcode/C39BarCode',
   'I25BarCode'                                                       => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/file/image/barcode/I25BarCode',

   'BasePdfDocument'                                                  => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/file/pdf/BasePdfDocument',
   'SimplePdfDocument'                                                => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/file/pdf/SimplePdfDocument',

   'BaseLock'                                                         => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/locking/BaseLock',
   'FileLock'                                                         => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/locking/FileLock',
   'Lock'                                                             => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/locking/Lock',
   'SystemFiveLock'                                                   => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/locking/SystemFiveLock',

   'NetTools'                                                         => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/net/NetTools',
   'TorHelper'                                                        => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/net/TorHelper',

   'CurlHttpClient'                                                   => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/net/http/CurlHttpClient',
   'CurlHttpResponse'                                                 => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/net/http/CurlHttpResponse',
   'HeaderParser'                                                     => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/net/http/HeaderParser',
   'HeaderUtils'                                                      => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/net/http/HeaderUtils',
   'HttpClient'                                                       => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/net/http/HttpClient',
   'HttpRequest'                                                      => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/net/http/HttpRequest',
   'HttpResponse'                                                     => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/net/http/HttpResponse',

   'CLIMailer'                                                        => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/net/mail/CLIMailer',
   'FileSocketMailer'                                                 => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/net/mail/FileSocketMailer',
   'Mailer'                                                           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/net/mail/Mailer',
   'PHPMailer'                                                        => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/net/mail/PHPMailer',
   'SMTPMailer'                                                       => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/net/mail/SMTPMailer',

   'ClickatellSMSMessenger'                                           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/net/messenger/ClickatellSMSMessenger',
   'ICQMessenger'                                                     => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/net/messenger/ICQMessenger',
   'IRCMessenger'                                                     => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/net/messenger/IRCMessenger',
   'Messenger'                                                        => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/net/messenger/Messenger',

   'Action'                                                           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/struts/Action',
   'ActionForm'                                                       => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/struts/ActionForm',
   'ActionForward'                                                    => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/struts/ActionForward',
   'ActionMapping'                                                    => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/struts/ActionMapping',
   'HttpSession'                                                      => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/struts/HttpSession',
   'Module'                                                           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/struts/Module',
   'PageContext'                                                      => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/struts/PageContext',
   'Request'                                                          => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/struts/Request',
   'RequestProcessor'                                                 => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/struts/RequestProcessor',
   'Response'                                                         => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/struts/Response',
   'RoleProcessor'                                                    => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/struts/RoleProcessor',
   'Struts'                                                           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/struts/Struts',
   'StrutsController'                                                 => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/struts/StrutsController',
   'Tile'                                                             => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/struts/Tile',

   'Date'                                                             => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/util/Date',
   'DebugTools'                                                       => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/util/DebugTools',
   'Logger'                                                           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/util/Logger',
   'String'                                                           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/util/String',
   'System'                                                           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/util/System',
   'rosasurfer\ministruts\util\Validator'                             => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/util/Validator',
);
