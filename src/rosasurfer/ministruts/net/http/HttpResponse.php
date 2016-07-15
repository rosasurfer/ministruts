<?php
use rosasurfer\ministruts\core\Object;


/**
 * HttpResponse
 *
 * Abstrakte Basisklasse für HttpResponse-Implementierungen.
 *
 * TODO: toString()-Methode implementieren, die alle Header anzeigt
 */
abstract class HttpResponse extends Object {


   // Server status codes; see RFC 2068.

   /**
    * Status code (100) indicating the client can continue.
    */
   const SC_CONTINUE = 100;

   /**
    * Status code (101) indicating the server is switching protocols according to Upgrade header.
    */
   const SC_SWITCHING_PROTOCOLS = 101;

   /**
    * Status code (200) indicating the request succeeded normally.
    */
   const SC_OK = 200;

   /**
    * Status code (201) indicating the request succeeded and created a new resource on the server.
    */
   const SC_CREATED = 201;

   /**
    * Status code (202) indicating that a request was accepted for processing, but was not completed.
    */
   const SC_ACCEPTED = 202;

   /**
    * Status code (203) indicating that the meta information presented by the client did not originate
    * from the server.
    */
   const SC_NON_AUTHORITATIVE_INFORMATION = 203;

   /**
    * Status code (204) indicating that the request succeeded but thatthere was no new information
    * to return.
    */
   const SC_NO_CONTENT = 204;

   /**
    * Status code (205) indicating that the agent should reset the document view which caused the
    * request to be sent.
    */
   const SC_RESET_CONTENT = 205;

   /**
    * Status code (206) indicating that the server has fulfilled the partial GET request for the
    * resource.
    */
   const SC_PARTIAL_CONTENT = 206;

   /**
    * Status code (300) indicating that the requested resource corresponds to any one of a set of
    * representations, each with its own specific location.
    */
   const SC_MULTIPLE_CHOICES = 300;

   /**
    * Status code (301) indicating that the resource has permanently moved to a new location, and
    * that future references should use a new URI with their requests.
    */
   const SC_MOVED_PERMANENTLY = 301;

   /**
    * Status code (302) indicating that the resource has temporarily moved to another location, but
    * that future references should still use the original URI to access the resource.
    */
   const SC_MOVED_TEMPORARILY = 302;

   /**
    * Status code (303) indicating that the response to the request can be found under a different
    * URI.
    */
   const SC_SEE_OTHER = 303;

   /**
    * Status code (304) indicating that a conditional GET operation found that the resource was
    * available and not modified.
    */
   const SC_NOT_MODIFIED = 304;

   /**
    * Status code (305) indicating that the requested resource must be accessed through the proxy
    * given by the "Location" header.
    */
   const SC_USE_PROXY = 305;

   /**
    * Status code (307) indicating that the requested resource resides temporarily under a different
    * URI. The temporary URI should be given by the "Location" header of the response.
    */
   const SC_TEMPORARY_REDIRECT = 307;

   /**
    * Status code (400) indicating the request sent by the client was syntactically incorrect.
    */
   const SC_BAD_REQUEST = 400;

   /**
    * Status code (401) indicating that the request requires HTTP authentication.
    */
   const SC_UNAUTHORIZED = 401;

   /**
    * Status code (402) reserved for future use.
    */
   const SC_PAYMENT_REQUIRED = 402;

   /**
    * Status code (403) indicating the server understood the request but refused to fulfill it.
    */
   const SC_FORBIDDEN = 403;

   /**
    * Status code (404) indicating that the requested resource is not available.
    */
   const SC_NOT_FOUND = 404;

   /**
    * Status code (405) indicating that the method specified in the "Request-Line" is not allowed for
    * the resource identified by the "Request-URI".
    */
   const SC_METHOD_NOT_ALLOWED = 405;

   /**
    * Status code (406) indicating that the resource identified by the request is only capable of
    * generating response entities which have content characteristics not acceptable according to
    * the accept headers sent in the request.
    */
   const SC_NOT_ACCEPTABLE = 406;

   /**
    * Status code (407) indicating that the client must first authenticate itself with the proxy.
    */
   const SC_PROXY_AUTHENTICATION_REQUIRED = 407;

   /**
    * Status code (408) indicating that the client did not produce a request within the time that
    * the server was prepared to wait.
    */
   const SC_REQUEST_TIMEOUT = 408;

   /**
    * Status code (409) indicating that the request could not be completed due to a conflict with
    * the current state of the resource.
    */
   const SC_CONFLICT = 409;

   /**
    * Status code (410) indicating that the resource is no longer available at the server and no
    * forwarding address is known. This condition should be considered permanent.
    */
   const SC_GONE = 410;

   /**
    * Status code (411) indicating that the request cannot be handled without a defined
    * "Content-Length" header.
    */
   const SC_LENGTH_REQUIRED = 411;

   /**
    * Status code (412) indicating that the precondition given in one or more of the request headers
    * evaluated to FALSE when it was tested on the server.
    */
   const SC_PRECONDITION_FAILED = 412;

   /**
    * Status code (413) indicating that the server is refusing to process the request because the
    * request entity is larger than the server is willing or able to process.
    */
   const SC_REQUEST_ENTITY_TOO_LARGE = 413;

   /**
    * Status code (414) indicating that the server is refusing to service the request because the
    * "Request-URI" is longer than the server is willing to interpret.
    */
   const SC_REQUEST_URI_TOO_LONG = 414;

   /**
    * Status code (415) indicating that the server is refusing to service the request because the
    * entity of the request is in a format not supported by the requested resource for the requested
    * method.
    */
   const SC_UNSUPPORTED_MEDIA_TYPE = 415;

   /**
    * Status code (416) indicating that the server cannot serve the requested byte range.
    */
   const SC_REQUESTED_RANGE_NOT_SATISFIABLE = 416;

   /**
    * Status code (417) indicating that the server could not meet the expectation given in the
    * "Expect" request header.
    */
   const SC_EXPECTATION_FAILED = 417;

   /**
    * Status code (500) indicating an error inside the HTTP server which prevented it from fulfilling
    * the request.
    */
   const SC_INTERNAL_SERVER_ERROR = 500;

   /**
    * Status code (501) indicating the HTTP server does not support the functionality needed to
    * fulfill the request.
    */
   const SC_NOT_IMPLEMENTED = 501;

   /**
    * Status code (502) indicating that the HTTP server received an invalid response from a server
    * it consulted when acting as a proxy or gateway.
    */
   const SC_BAD_GATEWAY = 502;

   /**
    * Status code (503) indicating that the HTTP server is temporarily overloaded, and unable to
    * handle the request.
    */
   const SC_SERVICE_UNAVAILABLE = 503;

   /**
    * Status code (504) indicating that the server did not receive a timely response from the
    * upstream server while acting as a gateway or proxy.
    */
   const SC_GATEWAY_TIMEOUT = 504;

   /**
    * Status code (505) indicating that the server does not support or refuses to support the HTTP
    * protocol version that was used in the request message.
    */
   const SC_HTTP_VERSION_NOT_SUPPORTED = 505;


   /**
    * HTTP status code descriptions
    *
    * TODO: HttpResponse::$sc - unmöglicher Name
    */
   public static $sc = array(self:: SC_CONTINUE                        => 'SC_CONTINUE'                       ,
                             self:: SC_SWITCHING_PROTOCOLS             => 'SC_SWITCHING_PROTOCOLS'            ,
                             self:: SC_OK                              => 'SC_OK'                             ,
                             self:: SC_CREATED                         => 'SC_CREATED'                        ,
                             self:: SC_ACCEPTED                        => 'SC_ACCEPTED'                       ,
                             self:: SC_NON_AUTHORITATIVE_INFORMATION   => 'SC_NON_AUTHORITATIVE_INFORMATION'  ,
                             self:: SC_NO_CONTENT                      => 'SC_NO_CONTENT'                     ,
                             self:: SC_RESET_CONTENT                   => 'SC_RESET_CONTENT'                  ,
                             self:: SC_PARTIAL_CONTENT                 => 'SC_PARTIAL_CONTENT'                ,
                             self:: SC_MULTIPLE_CHOICES                => 'SC_MULTIPLE_CHOICES'               ,
                             self:: SC_MOVED_PERMANENTLY               => 'SC_MOVED_PERMANENTLY'              ,
                             self:: SC_MOVED_TEMPORARILY               => 'SC_MOVED_TEMPORARILY'              ,
                             self:: SC_SEE_OTHER                       => 'SC_SEE_OTHER'                      ,
                             self:: SC_NOT_MODIFIED                    => 'SC_NOT_MODIFIED'                   ,
                             self:: SC_USE_PROXY                       => 'SC_USE_PROXY'                      ,
                             self:: SC_TEMPORARY_REDIRECT              => 'SC_TEMPORARY_REDIRECT'             ,
                             self:: SC_BAD_REQUEST                     => 'SC_BAD_REQUEST'                    ,
                             self:: SC_UNAUTHORIZED                    => 'SC_UNAUTHORIZED'                   ,
                             self:: SC_PAYMENT_REQUIRED                => 'SC_PAYMENT_REQUIRED'               ,
                             self:: SC_FORBIDDEN                       => 'SC_FORBIDDEN'                      ,
                             self:: SC_NOT_FOUND                       => 'SC_NOT_FOUND'                      ,
                             self:: SC_METHOD_NOT_ALLOWED              => 'SC_METHOD_NOT_ALLOWED'             ,
                             self:: SC_NOT_ACCEPTABLE                  => 'SC_NOT_ACCEPTABLE'                 ,
                             self:: SC_PROXY_AUTHENTICATION_REQUIRED   => 'SC_PROXY_AUTHENTICATION_REQUIRED'  ,
                             self:: SC_REQUEST_TIMEOUT                 => 'SC_REQUEST_TIMEOUT'                ,
                             self:: SC_CONFLICT                        => 'SC_CONFLICT'                       ,
                             self:: SC_GONE                            => 'SC_GONE'                           ,
                             self:: SC_LENGTH_REQUIRED                 => 'SC_LENGTH_REQUIRED'                ,
                             self:: SC_PRECONDITION_FAILED             => 'SC_PRECONDITION_FAILED'            ,
                             self:: SC_REQUEST_ENTITY_TOO_LARGE        => 'SC_REQUEST_ENTITY_TOO_LARGE'       ,
                             self:: SC_REQUEST_URI_TOO_LONG            => 'SC_REQUEST_URI_TOO_LONG'           ,
                             self:: SC_UNSUPPORTED_MEDIA_TYPE          => 'SC_UNSUPPORTED_MEDIA_TYPE'         ,
                             self:: SC_REQUESTED_RANGE_NOT_SATISFIABLE => 'SC_REQUESTED_RANGE_NOT_SATISFIABLE',
                             self:: SC_EXPECTATION_FAILED              => 'SC_EXPECTATION_FAILED'             ,
                             self:: SC_INTERNAL_SERVER_ERROR           => 'SC_INTERNAL_SERVER_ERROR'          ,
                             self:: SC_NOT_IMPLEMENTED                 => 'SC_NOT_IMPLEMENTED'                ,
                             self:: SC_BAD_GATEWAY                     => 'SC_BAD_GATEWAY'                    ,
                             self:: SC_SERVICE_UNAVAILABLE             => 'SC_SERVICE_UNAVAILABLE'            ,
                             self:: SC_GATEWAY_TIMEOUT                 => 'SC_GATEWAY_TIMEOUT'                ,
                             self:: SC_HTTP_VERSION_NOT_SUPPORTED      => 'SC_HTTP_VERSION_NOT_SUPPORTED'     ,
                             );

   /**
    * Gibt den HTTP-Status zurück.
    *
    * @return int - Statuscode
    */
   abstract public function getStatus();


   /**
    * Gibt die erhaltenen Header zurück.
    *
    * @return array - Array mit Headern
    */
   abstract public function getHeaders();


   /**
    * Ob ein Header mit dem angegebenen Namen existiert.
    *
    * @param  string $name - Name des Headers
    *
    * @return bool
    */
   abstract public function isHeader($name);


   /**
    * Gibt den Header mit dem angegebenen Namen zurück.
    *
    * @param  string $name - Name des Headers
    *
    * @return mixed - String oder Array mit dem/den gefundenen Header(n)
    */
   abstract public function getHeader($name);


   /**
    * Gibt den Content des HttpResponse zurück.
    *
    * @return string - Content
    */
   abstract public function getContent();
}
