<?php
use const rosasurfer\MINISTRUTS_ROOT;


/**
 * Class map for the internal class loader
 */
return array(
   'rosasurfer\\acl\\ACL'                                   => MINISTRUTS_ROOT.'/src/rosasurfer/acl/ACL',
   'rosasurfer\\acl\\AclManager'                            => MINISTRUTS_ROOT.'/src/rosasurfer/acl/AclManager',
   'rosasurfer\\acl\\IAccessControlled'                     => MINISTRUTS_ROOT.'/src/rosasurfer/acl/IAccessControlled',
   'rosasurfer\\acl\\IIdentityNode'                         => MINISTRUTS_ROOT.'/src/rosasurfer/acl/IIdentityNode',
   'rosasurfer\\acl\\IPolicyAware'                          => MINISTRUTS_ROOT.'/src/rosasurfer/acl/IPolicyAware',
   'rosasurfer\\acl\\IRoleAware'                            => MINISTRUTS_ROOT.'/src/rosasurfer/acl/IRoleAware',
   'rosasurfer\\acl\\Policy'                                => MINISTRUTS_ROOT.'/src/rosasurfer/acl/Policy',
   'rosasurfer\\acl\\PolicyInterface'                       => MINISTRUTS_ROOT.'/src/rosasurfer/acl/PolicyInterface',
   'rosasurfer\\acl\\Role'                                  => MINISTRUTS_ROOT.'/src/rosasurfer/acl/Role',
   'rosasurfer\\acl\\RoleInterface'                         => MINISTRUTS_ROOT.'/src/rosasurfer/acl/RoleInterface',
   'rosasurfer\\acl\\adapter\\AdapterInterface'             => MINISTRUTS_ROOT.'/src/rosasurfer/acl/adapter/AdapterInterface',
   'rosasurfer\\acl\\adapter\\ConfigFileAdapter'            => MINISTRUTS_ROOT.'/src/rosasurfer/acl/adapter/ConfigFileAdapter',
   'rosasurfer\\acl\\adapter\\DbAdapter'                    => MINISTRUTS_ROOT.'/src/rosasurfer/acl/adapter/DbAdapter',
   'rosasurfer\\acl\\adapter\\IniFileAdapter'               => MINISTRUTS_ROOT.'/src/rosasurfer/acl/adapter/IniFileAdapter',

   'rosasurfer\\cache\\ApcCache'                            => MINISTRUTS_ROOT.'/src/rosasurfer/cache/ApcCache',
   'rosasurfer\\cache\\Cache'                               => MINISTRUTS_ROOT.'/src/rosasurfer/cache/Cache',
   'rosasurfer\\cache\\CachePeer'                           => MINISTRUTS_ROOT.'/src/rosasurfer/cache/CachePeer',
   'rosasurfer\\cache\\FileSystemCache'                     => MINISTRUTS_ROOT.'/src/rosasurfer/cache/FileSystemCache',
   'rosasurfer\\cache\\ReferencePool'                       => MINISTRUTS_ROOT.'/src/rosasurfer/cache/ReferencePool',

   'rosasurfer\\config\\Config'                             => MINISTRUTS_ROOT.'/src/rosasurfer/config/Config',
   'rosasurfer\\config\\ConfigInterface'                    => MINISTRUTS_ROOT.'/src/rosasurfer/config/ConfigInterface',

   'rosasurfer\\core\\Object'                               => MINISTRUTS_ROOT.'/src/rosasurfer/core/Object',
   'rosasurfer\\core\\Singleton'                            => MINISTRUTS_ROOT.'/src/rosasurfer/core/Singleton',
   'rosasurfer\\core\\StaticClass'                          => MINISTRUTS_ROOT.'/src/rosasurfer/core/StaticClass',

   'rosasurfer\\dao\\CommonDAO'                             => MINISTRUTS_ROOT.'/src/rosasurfer/dao/CommonDAO',
   'rosasurfer\\dao\\DaoWorker'                             => MINISTRUTS_ROOT.'/src/rosasurfer/dao/DaoWorker',
   'rosasurfer\\dao\\PersistableObject'                     => MINISTRUTS_ROOT.'/src/rosasurfer/dao/PersistableObject',

   'rosasurfer\\db\\DB'                                     => MINISTRUTS_ROOT.'/src/rosasurfer/db/DB',
   'rosasurfer\\db\\DBPool'                                 => MINISTRUTS_ROOT.'/src/rosasurfer/db/DBPool',
   'rosasurfer\\db\\MySQLConnector'                         => MINISTRUTS_ROOT.'/src/rosasurfer/db/MySQLConnector',

   'rosasurfer\\dependency\\ChainedDependency'              => MINISTRUTS_ROOT.'/src/rosasurfer/dependency/ChainedDependency',
   'rosasurfer\\dependency\\Dependency'                     => MINISTRUTS_ROOT.'/src/rosasurfer/dependency/Dependency',
   'rosasurfer\\dependency\\FileDependency'                 => MINISTRUTS_ROOT.'/src/rosasurfer/dependency/FileDependency',

   'rosasurfer\\exception\\BaseException'                   => MINISTRUTS_ROOT.'/src/rosasurfer/exception/BaseException',
   'rosasurfer\\exception\\BusinessRuleException'           => MINISTRUTS_ROOT.'/src/rosasurfer/exception/BusinessRuleException',
   'rosasurfer\\exception\\ClassNotFoundException'          => MINISTRUTS_ROOT.'/src/rosasurfer/exception/ClassNotFoundException',
   'rosasurfer\\exception\\ConcurrentModificationException' => MINISTRUTS_ROOT.'/src/rosasurfer/exception/ConcurrentModificationException',
   'rosasurfer\\exception\\DatabaseException'               => MINISTRUTS_ROOT.'/src/rosasurfer/exception/DatabaseException',
   'rosasurfer\\exception\\FileNotFoundException'           => MINISTRUTS_ROOT.'/src/rosasurfer/exception/FileNotFoundException',
   'rosasurfer\\exception\\IOException'                     => MINISTRUTS_ROOT.'/src/rosasurfer/exception/IOException',
   'rosasurfer\\exception\\IRosasurferException'            => MINISTRUTS_ROOT.'/src/rosasurfer/exception/IRosasurferException',
   'rosasurfer\\exception\\IllegalAccessException'          => MINISTRUTS_ROOT.'/src/rosasurfer/exception/IllegalAccessException',
   'rosasurfer\\exception\\IllegalArgumentException'        => MINISTRUTS_ROOT.'/src/rosasurfer/exception/IllegalArgumentException',
   'rosasurfer\\exception\\IllegalStateException'           => MINISTRUTS_ROOT.'/src/rosasurfer/exception/IllegalStateException',
   'rosasurfer\\exception\\IllegalTypeException'            => MINISTRUTS_ROOT.'/src/rosasurfer/exception/IllegalTypeException',
   'rosasurfer\\exception\\InfrastructureException'         => MINISTRUTS_ROOT.'/src/rosasurfer/exception/InfrastructureException',
   'rosasurfer\\exception\\InvalidArgumentException'        => MINISTRUTS_ROOT.'/src/rosasurfer/exception/InvalidArgumentException',
   'rosasurfer\\exception\\PHPError'                        => MINISTRUTS_ROOT.'/src/rosasurfer/exception/PHPError',
   'rosasurfer\\exception\\PermissionDeniedException'       => MINISTRUTS_ROOT.'/src/rosasurfer/exception/PermissionDeniedException',
   'rosasurfer\\exception\\RuntimeException'                => MINISTRUTS_ROOT.'/src/rosasurfer/exception/RuntimeException',
   'rosasurfer\\exception\\TRosasurferException'            => MINISTRUTS_ROOT.'/src/rosasurfer/exception/TRosasurferException',
   'rosasurfer\\exception\\UnimplementedFeatureException'   => MINISTRUTS_ROOT.'/src/rosasurfer/exception/UnimplementedFeatureException',
   'rosasurfer\\exception\\UnsupportedMethodException'      => MINISTRUTS_ROOT.'/src/rosasurfer/exception/UnsupportedMethodException',

   'rosasurfer\\lock\\BaseLock'                             => MINISTRUTS_ROOT.'/src/rosasurfer/lock/BaseLock',
   'rosasurfer\\lock\\FileLock'                             => MINISTRUTS_ROOT.'/src/rosasurfer/lock/FileLock',
   'rosasurfer\\lock\\Lock'                                 => MINISTRUTS_ROOT.'/src/rosasurfer/lock/Lock',
   'rosasurfer\\lock\\SystemFiveLock'                       => MINISTRUTS_ROOT.'/src/rosasurfer/lock/SystemFiveLock',

   'rosasurfer\\net\\NetTools'                              => MINISTRUTS_ROOT.'/src/rosasurfer/net/NetTools',
   'rosasurfer\\net\\TorHelper'                             => MINISTRUTS_ROOT.'/src/rosasurfer/net/TorHelper',

   'rosasurfer\\net\\http\\CurlHttpClient'                  => MINISTRUTS_ROOT.'/src/rosasurfer/net/http/CurlHttpClient',
   'rosasurfer\\net\\http\\CurlHttpResponse'                => MINISTRUTS_ROOT.'/src/rosasurfer/net/http/CurlHttpResponse',
   'rosasurfer\\net\\http\\HeaderParser'                    => MINISTRUTS_ROOT.'/src/rosasurfer/net/http/HeaderParser',
   'rosasurfer\\net\\http\\HeaderUtils'                     => MINISTRUTS_ROOT.'/src/rosasurfer/net/http/HeaderUtils',
   'rosasurfer\\net\\http\\HttpClient'                      => MINISTRUTS_ROOT.'/src/rosasurfer/net/http/HttpClient',
   'rosasurfer\\net\\http\\HttpRequest'                     => MINISTRUTS_ROOT.'/src/rosasurfer/net/http/HttpRequest',
   'rosasurfer\\net\\http\\HttpResponse'                    => MINISTRUTS_ROOT.'/src/rosasurfer/net/http/HttpResponse',

   'CLIMailer'                                              => MINISTRUTS_ROOT.'/src/rosasurfer/net/mail/CLIMailer',
   'FileSocketMailer'                                       => MINISTRUTS_ROOT.'/src/rosasurfer/net/mail/FileSocketMailer',
   'Mailer'                                                 => MINISTRUTS_ROOT.'/src/rosasurfer/net/mail/Mailer',
   'PHPMailer'                                              => MINISTRUTS_ROOT.'/src/rosasurfer/net/mail/PHPMailer',
   'SMTPMailer'                                             => MINISTRUTS_ROOT.'/src/rosasurfer/net/mail/SMTPMailer',

   'rosasurfer\\net\\messenger\\Messenger'                  => MINISTRUTS_ROOT.'/src/rosasurfer/net/messenger/Messenger',
   'rosasurfer\\net\\messenger\\im\\IRCMessenger'           => MINISTRUTS_ROOT.'/src/rosasurfer/net/messenger/im/IRCMessenger',
   'rosasurfer\\net\\messenger\\sms\\ClickatellMessenger'   => MINISTRUTS_ROOT.'/src/rosasurfer/net/messenger/sms/ClickatellMessenger',
   'rosasurfer\\net\\messenger\\sms\\NexmoMessenger'        => MINISTRUTS_ROOT.'/src/rosasurfer/net/messenger/sms/NexmoMessenger',

   'Action'                                                 => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/Action',
   'ActionForm'                                             => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/ActionForm',
   'ActionForward'                                          => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/ActionForward',
   'ActionMapping'                                          => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/ActionMapping',
   'HttpSession'                                            => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/HttpSession',
   'Module'                                                 => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/Module',
   'PageContext'                                            => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/PageContext',
   'rosasurfer\\ministruts\\Request'                        => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/Request',
   'RequestProcessor'                                       => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/RequestProcessor',
   'Response'                                               => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/Response',
   'RoleProcessor'                                          => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/RoleProcessor',
   'rosasurfer\\ministruts\\StrutsController'               => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/StrutsController',
   'Tile'                                                   => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/Tile',
   'rosasurfer\\ministruts\\url\\Url'                       => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/url/Url',
   'rosasurfer\\ministruts\\url\\VersionedUrl'              => MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/url/VersionedUrl',

   'Date'                                                   => MINISTRUTS_ROOT.'/src/rosasurfer/util/Date',
   'DebugTools'                                             => MINISTRUTS_ROOT.'/src/rosasurfer/util/DebugTools',
   'Logger'                                                 => MINISTRUTS_ROOT.'/src/rosasurfer/util/Logger',
   'System'                                                 => MINISTRUTS_ROOT.'/src/rosasurfer/util/System',
   'rosasurfer\\util\\Validator'                            => MINISTRUTS_ROOT.'/src/rosasurfer/util/Validator',
);
