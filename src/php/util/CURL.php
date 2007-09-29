<?
/**
 * CURL
 */
class CURL extends StaticFactory {


   private static $errors = array(CURLE_OK                      => 'CURLE_OK'                     ,
                                  CURLE_UNSUPPORTED_PROTOCOL    => 'CURLE_UNSUPPORTED_PROTOCOL'   ,
                                  CURLE_FAILED_INIT             => 'CURLE_FAILED_INIT'            ,
                                  CURLE_URL_MALFORMAT           => 'CURLE_URL_MALFORMAT'          ,
                                  CURLE_COULDNT_RESOLVE_PROXY   => 'CURLE_COULDNT_RESOLVE_PROXY'  ,
                                  CURLE_COULDNT_RESOLVE_HOST    => 'CURLE_COULDNT_RESOLVE_HOST'   ,
                                  CURLE_COULDNT_CONNECT         => 'CURLE_COULDNT_CONNECT'        ,
                                  CURLE_FTP_WEIRD_SERVER_REPLY  => 'CURLE_FTP_WEIRD_SERVER_REPLY' ,
                                  9                             => 'CURLE_REMOTE_ACCESS_DENIED'   ,
                                  CURLE_FTP_WEIRD_PASS_REPLY    => 'CURLE_FTP_WEIRD_PASS_REPLY'   ,
                                  CURLE_FTP_WEIRD_PASV_REPLY    => 'CURLE_FTP_WEIRD_PASV_REPLY'   ,
                                  CURLE_FTP_WEIRD_227_FORMAT    => 'CURLE_FTP_WEIRD_227_FORMAT'   ,
                                  CURLE_FTP_CANT_GET_HOST       => 'CURLE_FTP_CANT_GET_HOST'      ,
                                  17                            => 'CURLE_FTP_COULDNT_SET_TYPE'   ,
                                  CURLE_PARTIAL_FILE            => 'CURLE_PARTIAL_FILE'           ,
                                  CURLE_FTP_COULDNT_RETR_FILE   => 'CURLE_FTP_COULDNT_RETR_FILE'  ,
                                  21                            => 'CURLE_QUOTE_ERROR'            ,
                                  22                            => 'CURLE_HTTP_RETURNED_ERROR'    ,
                                  CURLE_WRITE_ERROR             => 'CURLE_WRITE_ERROR'            ,
                                  25                            => 'CURLE_UPLOAD_FAILED'          ,
                                  CURLE_READ_ERROR              => 'CURLE_READ_ERROR'             ,
                                  CURLE_OUT_OF_MEMORY           => 'CURLE_OUT_OF_MEMORY'          ,
                                  28                            => 'CURLE_OPERATION_TIMEDOUT'     ,
                                  CURLE_FTP_PORT_FAILED         => 'CURLE_FTP_PORT_FAILED'        ,
                                  CURLE_FTP_COULDNT_USE_REST    => 'CURLE_FTP_COULDNT_USE_REST'   ,
                                  33                            => 'CURLE_RANGE_ERROR'            ,
                                  CURLE_HTTP_POST_ERROR         => 'CURLE_HTTP_POST_ERROR'        ,
                                  CURLE_SSL_CONNECT_ERROR       => 'CURLE_SSL_CONNECT_ERROR'      ,
                                  CURLE_FTP_BAD_DOWNLOAD_RESUME => 'CURLE_FTP_BAD_DOWNLOAD_RESUME',
                                  CURLE_FILE_COULDNT_READ_FILE  => 'CURLE_FILE_COULDNT_READ_FILE' ,
                                  CURLE_LDAP_CANNOT_BIND        => 'CURLE_LDAP_CANNOT_BIND'       ,
                                  CURLE_LDAP_SEARCH_FAILED      => 'CURLE_LDAP_SEARCH_FAILED'     ,
                                  CURLE_FUNCTION_NOT_FOUND      => 'CURLE_FUNCTION_NOT_FOUND'     ,
                                  CURLE_ABORTED_BY_CALLBACK     => 'CURLE_ABORTED_BY_CALLBACK'    ,
                                  CURLE_BAD_FUNCTION_ARGUMENT   => 'CURLE_BAD_FUNCTION_ARGUMENT'  ,
                                  45                            => 'CURLE_INTERFACE_FAILED'       ,
                                  CURLE_TOO_MANY_REDIRECTS      => 'CURLE_TOO_MANY_REDIRECTS'     ,
                                  CURLE_UNKNOWN_TELNET_OPTION   => 'CURLE_UNKNOWN_TELNET_OPTION'  ,
                                  CURLE_TELNET_OPTION_SYNTAX    => 'CURLE_TELNET_OPTION_SYNTAX'   ,
                                  CURLE_SSL_PEER_CERTIFICATE    => 'CURLE_SSL_PEER_CERTIFICATE'   ,
                                  CURLE_GOT_NOTHING             => 'CURLE_GOT_NOTHING'            ,
                                  CURLE_SSL_ENGINE_NOTFOUND     => 'CURLE_SSL_ENGINE_NOTFOUND'    ,
                                  CURLE_SSL_ENGINE_SETFAILED    => 'CURLE_SSL_ENGINE_SETFAILED'   ,
                                  CURLE_SEND_ERROR              => 'CURLE_SEND_ERROR'             ,
                                  CURLE_RECV_ERROR              => 'CURLE_RECV_ERROR'             ,
                                  CURLE_SSL_CERTPROBLEM         => 'CURLE_SSL_CERTPROBLEM'        ,
                                  CURLE_SSL_CIPHER              => 'CURLE_SSL_CIPHER'             ,
                                  CURLE_SSL_CACERT              => 'CURLE_SSL_CACERT'             ,
                                  CURLE_BAD_CONTENT_ENCODING    => 'CURLE_BAD_CONTENT_ENCODING'   ,
                                  CURLE_LDAP_INVALID_URL        => 'CURLE_LDAP_INVALID_URL'       ,
                                  CURLE_FILESIZE_EXCEEDED       => 'CURLE_FILESIZE_EXCEEDED'      ,
                                  64                            => 'CURLE_USE_SSL_FAILED'         ,
                                  65                            => 'CURLE_SEND_FAIL_REWIND'       ,
                                  66                            => 'CURLE_SSL_ENGINE_INITFAILED'  ,
                                  67                            => 'CURLE_LOGIN_DENIED'           ,
                                  68                            => 'CURLE_TFTP_NOTFOUND'          ,
                                  69                            => 'CURLE_TFTP_PERM'              ,
                                  70                            => 'CURLE_REMOTE_DISK_FULL'       ,
                                  71                            => 'CURLE_TFTP_ILLEGAL'           ,
                                  72                            => 'CURLE_TFTP_UNKNOWNID'         ,
                                  73                            => 'CURLE_REMOTE_FILE_EXISTS'     ,
                                  74                            => 'CURLE_TFTP_NOSUCHUSER'        ,
                                  75                            => 'CURLE_CONV_FAILED'            ,
                                  76                            => 'CURLE_CONV_REQD'              ,
                                  77                            => 'CURLE_SSL_CACERT_BADFILE'     ,
                                  78                            => 'CURLE_REMOTE_FILE_NOT_FOUND'  ,
                                  79                            => 'CURLE_SSH'                    ,
                                  80                            => 'CURLE_SSL_SHUTDOWN_FAILED'    );


   /**
    * Gibt eine Beschreibung des letzten CURL-Fehlers zurück.
    *
    * @param resource $handle - CURL-Handle
    */
   public static function getError(&$handle) {
      $error = curl_error($handle);

      if (empty($error)) {
         $error = curl_errno($handle);
         if (isSet(self::$errors[$error])) {
            $error = self::$errors[$error];
         }
         else {
            $error = (string) $error;
         }
      }
      return $error;
   }


   /**
    * Gibt den letzten CURL-Fehlercode zurück.
    *
    * @param resource $handle - CURL-Handle
    */
   public static function getErrorNo(&$handle) {
      return curl_errno($handle);
   }


   /**
    * Gibt den HTTP-Statuscode einer CURL-Verbindung zurück.
    *
    * @param resource $handle - CURL-Handle
    */
   public static function getHttpStatusCode(&$handle) {
      return (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
   }
}
