<?php
namespace rosasurfer\core\proxy;

use rosasurfer\ministruts\ActionMapping;
use rosasurfer\ministruts\HttpSession;
use rosasurfer\ministruts\Module;


/**
 * Request
 *
 * Proxy for the "request" implementation currently registered in the service container.
 *
 * Default implementation: {@link \rosasurfer\ministruts\Request}
 *
 * @method static \rosasurfer\ministruts\Request            instance()                                                                Get the object behind the proxy.
 * @method static string                                    getMethod()                                                               Return the HTTP method of the request.
 * @method static bool                                      isGet()                                                                   Whether the request is a GET request.
 * @method static bool                                      isPost()                                                                  Whether the request is a POST request.
 * @method static bool                                      isSecure()                                                                Whether the request was made over a secure connection (HTTPS).
 * @method static string|null                               getParameter(string $name)                                                Return the single $_REQUEST parameter with the specified name. If multiple $_REQUEST parameters with that name have been transmitted, the last one is returned. A transmitted array of $_REQUEST parameters with that name is ignored.
 * @method static string[]                                  getParameters(string $name)                                               Return an array of $_REQUEST parameters with the specified name. A single transmitted $_REQUEST parameter with that name is ignored.
 * @method static string|null                               getGetParameter(string $name)                                             Return the single $_GET parameter with the specified name. If multiple $_GET parameters with that name have been transmitted, the last one is returned. A transmitted array of $_GET parameters with that name is ignored.
 * @method static string[]                                  getGetParameters(string $name)                                            Return an array of $_GET parameters with the specified name. A single transmitted $_GET parameter with that name is ignored.
 * @method static string|null                               getPostParameter(string $name)                                            Return the single $_POST parameter with the specified name. If multiple $_POST parameters with that name have been transmitted, the last one is returned. A transmitted array of $_POST parameters with that name is ignored.
 * @method static string[]                                  getPostParameters(string $name)                                           Return an array of $_POST parameters with the specified name. A single transmitted $_POST parameter with that name is ignored.
 * @method static array                                     getFiles()                                                                Return an object-oriented representation of the files uploaded with the request. The PHP array structure of $_FILES is converted to normalized arrays.
 * @method static array|null                                getFile(string $name)                                                     Return an object-oriented representation of a single file uploaded with the request.
 * @method static string                                    getHostname()                                                             Return the host name the request was made to.
 * @method static string                                    getHostUrl()                                                              Return the root URL of the server the request was made to. This value always ends with a slash "/".
 * @method static string                                    getUrl()                                                                  Return the full URL of the request.
 * @method static string                                    getUri()                                                                  Return the URI of the request (the value in the first line of the HTTP protocol). This value always starts with a slash "/".
 * @method static string                                    getPath()                                                                 Return the path fragment of the request's URI. This value always starts with a slash "/".
 * @method static string                                    getApplicationUrl()                                                       Return the root URL of the application. This value always ends with a slash "/".
 * @method static string                                    getApplicationRelativeUri()                                               Return the request's URI relative to the application's base URL. This value always starts with a slash "/".
 * @method static string                                    getApplicationRelativePath()                                              Return the request's path fragment relative to the application's base URL. This value always starts with a slash "/".
 * @method static string                                    getApplicationBaseUri()                                                   Return the application's base URI. The value always starts and ends with a slash "/".
 * @method static string                                    getQueryString()                                                          Return the query string of the request's URL.
 * @method static string                                    getRemoteAddress()                                                        Return the IP address the request was made from.
 * @method static string                                    getRemoteHostname()                                                       Return the name of the host the request was made from.
 * @method static string|null                               getForwardedRemoteAddress()                                               Return the value of a transmitted "X-Forwarded-For" header.
 * @method static string                                    getContent()                                                              Return the content of the request (the body). For file uploads the method doesn't return the real binary content. Instead it returns available metadata.
 * @method static string|null                               getContentType()                                                          Return the "Content-Type" header of the request. If multiple "Content-Type" headers have been transmitted the first one is returned.
 * @method static \rosasurfer\ministruts\HttpSession        getSession()                                                              Return the current HTTP session object. If a session object does not yet exist, one is created.
 * @method static bool                                      isSession()                                                               Whether an HTTP session was started during the request. Not whether the session is still open (active).
 * @method static bool                                      isSessionAttribute(string $key)                                           Whether a session attribute of the specified name exists. If no session exists none is started.
 * @method static string                                    getSessionId()                                                            Return the session id transmitted with the request (not the id sent with the response, which may differ).
 * @method static bool                                      hasSessionId()                                                            Whether a valid session id was transmitted with the request. An invalid id is a URL based session id when the php.ini setting "session.use_only_cookies" is enabled.
 * @method static                                           destroySession()                                                          Destroy the current session and it's data.
 * @method static string|null                               getHeader(string $name)                                                   Return the first transmitted header with the specified name.
 * @method static string[]                                  getHeaders(string|string[] $names=[])                                     Return all headers with the specified name as an associative array of header values (in transmitted order).
 * @method static string|null                               getHeaderValue(string|string[] $names)                                    Return a single value of all specified header(s). If multiple headers are specified or multiple headers have been transmitted, return all values as one comma-separated value (in transmission order).
 * @method static string[]                                  getHeaderValues(string|string[] $names)                                   Return the values of all specified header(s) as an array (in transmission order).
 * @method static mixed                                     getAttribute(string $name)                                                Return a value stored in the request's variables context under the specified name.
 * @method static array                                     getAttributes()                                                           Return all values stored in the request's variables context.
 * @method static                                           setAttribute(string $name, mixed $value)                                  Store a value in the request's variables context. May be used to transfer data from controllers or {@link Action}s * to views.
 * @method static                                           removeAttributes(string ...$names)                                        Remove the variable(s) with the specified name(s) from the request's variables context.
 * @method static                                           setCookie(string $name, string $value, int $expires=0, string $path=null) Send a cookie.
 * @method static bool                                      isUserInRole(string $role)                                                Whether the current web user owns the specified role.
 * @method static string|null                               getActionMessage(string $key=null)                                        Return the stored ActionMessage for the specified key, or the first ActionMessage if no key was given.
 * @method static string[]                                  getActionMessages()                                                       Return all stored ActionMessages, including ActionErrors.
 * @method static bool                                      isActionMessage(string|string[] $keys=null)                               Whether an ActionMessage exists for one of the specified keys, or for any key if no key was given.
 * @method static                                           setActionMessage(string $key, string $message)                            Store an ActionMessage for the specified key.
 * @method static string[]                                  removeActionMessages(string ...$keys)                                     Remove the ActionMessage(s) with the specified key(s).
 * @method static string|null                               getActionError(string $key=null)                                          Return the stored ActionError for the specified key, or the first ActionError if no key was given.
 * @method static string[]                                  getActionErrors()                                                         Return all stored ActionErrors.
 * @method static bool                                      isActionError(string|string[] $keys=null)                                 Whether an ActionError exists for one of the specified keys, or for any key if no key was given.
 * @method static                                           setActionError(string $key, string $message)                              Store an ActionError for the specified key.
 * @method static string[]                                  removeActionErrors(string ...$keys)                                       Remove the ActionError(s) with the specified key(s).
 * @method static \rosasurfer\ministruts\ActionMapping|null getMapping()                                                              Return the MiniStruts {@link ActionMapping} responsible for processing the current request.
 * @method static \rosasurfer\ministruts\Module|null        getModule()                                                               Return the MiniStruts {@link Module} the current request is assigned to.
 */
class Request extends Proxy {


    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected static function getServiceName() {
        return 'request';
        return \rosasurfer\ministruts\Request::class;
    }
}
