<?php
use const rosasurfer\ministruts\ROOT as MINISTRUTS_ROOT;


/**
 * Class map for class loader (fastest way to load classes)
 */
return array(
   'System'                                                           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/System',

   'ApcCache'                                                         => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/cache/ApcCache',
   'Cache'                                                            => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/cache/Cache',
   'CachePeer'                                                        => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/cache/CachePeer',
   'FileSystemCache'                                                  => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/cache/FileSystemCache',
   'ReferencePool'                                                    => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/cache/ReferencePool',

   'Object'                                                           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/core/Object',
   'Singleton'                                                        => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/core/Singleton',
   'StaticClass'                                                      => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/core/StaticClass',

   'CommonDAO'                                                        => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/dao/CommonDAO',
   'DaoWorker'                                                        => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/dao/DaoWorker',
   'IDaoConnected'                                                    => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/dao/IDaoConnected',
   'PersistableObject'                                                => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/dao/PersistableObject',

   'DB'                                                               => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/db/DB',
   'DBPool'                                                           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/db/DBPool',
   'MySQLConnector'                                                   => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/db/MySQLConnector',

   'Dependency'                                                       => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/dependency/Dependency',
   'ChainedDependency'                                                => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/dependency/ChainedDependency',
   'FileDependency'                                                   => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/dependency/FileDependency',

   'rosasurfer\ministruts\exceptions\BaseException'                   => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exceptions/BaseException',
   'rosasurfer\ministruts\exceptions\BusinessRuleException'           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exceptions/BusinessRuleException',
   'rosasurfer\ministruts\exceptions\ClassNotFoundException'          => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exceptions/ClassNotFoundException',
   'rosasurfer\ministruts\exceptions\ConcurrentModificationException' => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exceptions/ConcurrentModificationException',
   'rosasurfer\ministruts\exceptions\DatabaseException'               => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exceptions/DatabaseException',
   'rosasurfer\ministruts\exceptions\FileNotFoundException'           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exceptions/FileNotFoundException',
   'rosasurfer\ministruts\exceptions\IRosasurferException'            => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exceptions/IRosasurferException',
   'rosasurfer\ministruts\exceptions\IOException'                     => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exceptions/IOException',
   'rosasurfer\ministruts\exceptions\IllegalAccessException'          => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exceptions/IllegalAccessException',
   'rosasurfer\ministruts\exceptions\IllegalArgumentException'        => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exceptions/IllegalArgumentException',
   'rosasurfer\ministruts\exceptions\IllegalStateException'           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exceptions/IllegalStateException',
   'rosasurfer\ministruts\exceptions\IllegalTypeException'            => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exceptions/IllegalTypeException',
   'rosasurfer\ministruts\exceptions\InfrastructureException'         => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exceptions/InfrastructureException',
   'rosasurfer\ministruts\exceptions\InvalidArgumentException'        => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exceptions/InvalidArgumentException',
   'rosasurfer\ministruts\exceptions\PHPError'                        => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exceptions/PHPError',
   'rosasurfer\ministruts\exceptions\PermissionDeniedException'       => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exceptions/PermissionDeniedException',
   'rosasurfer\ministruts\exceptions\RuntimeException'                => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exceptions/RuntimeException',
   'rosasurfer\ministruts\exceptions\UnimplementedFeatureException'   => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exceptions/UnimplementedFeatureException',
   'rosasurfer\ministruts\exceptions\UnsupportedMethodException'      => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/exceptions/UnsupportedMethodException',

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
   'FrontController'                                                  => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/struts/FrontController',
   'HttpSession'                                                      => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/struts/HttpSession',
   'Module'                                                           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/struts/Module',
   'PageContext'                                                      => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/struts/PageContext',
   'Request'                                                          => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/struts/Request',
   'RequestBase'                                                      => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/struts/RequestBase',
   'RequestProcessor'                                                 => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/struts/RequestProcessor',
   'Response'                                                         => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/struts/Response',
   'RoleProcessor'                                                    => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/struts/RoleProcessor',
   'Struts'                                                           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/struts/Struts',
   'Tile'                                                             => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/struts/Tile',

   'CommonValidator'                                                  => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/util/CommonValidator',
   'Config'                                                           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/util/Config',
   'Date'                                                             => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/util/Date',
   'Debug'                                                            => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/util/Debug',
   'Logger'                                                           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/util/Logger',
   'PHP'                                                              => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/util/PHP',
   'String'                                                           => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/util/String',
);
