<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\di\proxy;

use rosasurfer\ministruts\struts\ActionInput;
use rosasurfer\ministruts\struts\ActionMapping;
use rosasurfer\ministruts\struts\HttpSession;
use rosasurfer\ministruts\struts\Module;
use rosasurfer\ministruts\struts\Request as StrutsRequest;

/**
 * Request
 *
 * A {@link Proxy} for the "request" implementation currently registered in the service container.
 *
 * Default implementation: {@link \rosasurfer\ministruts\struts\Request}
 *
 * @method static StrutsRequest  instance()                                                                Get the object behind the proxy.
 * @method static string         getMethod()                                                               Return the HTTP method of the request.
 * @method static bool           isGet()                                                                   Whether the request is a GET request.
 * @method static bool           isPost()                                                                  Whether the request is a POST request.
 * @method static bool           isSecure()                                                                Whether the request was made over a secure connection (HTTPS).
 * @method static ActionInput    input()                                                                   Return an object wrapper for all raw input parameters of the request. It includes GET and POST parameters.
 * @method static ActionInput    get()                                                                     Return an object wrapper for all raw GET parameters of the request.
 * @method static ActionInput    post()                                                                    Return an object wrapper for all raw POST parameters of the request.
 * @method static scalar[][]     getFiles()                                                                Return an object-oriented representation of the files uploaded with the request. The PHP array structure of $_FILES is converted to normalized arrays.
 * @method static ?scalar[]      getFile(string $name)                                                     Return an object-oriented representation of a single file uploaded with the request.
 * @method static string         getHostname()                                                             Return the host name the request was made to.
 * @method static string         getHostUrl()                                                              Return the root URL of the server the request was made to. This value always ends with a slash "/".
 * @method static string         getUrl()                                                                  Return the full URL of the request.
 * @method static string         getUri()                                                                  Return the URI of the request (the value in the first line of the HTTP protocol). This value always starts with a slash "/".
 * @method static string         getPath()                                                                 Return the path fragment of the request's URI. This value always starts with a slash "/".
 * @method static string         getApplicationUrl()                                                       Return the root URL of the application. This value always ends with a slash "/".
 * @method static string         getApplicationRelativeUri()                                               Return the request's URI relative to the application's base URL. This value always starts with a slash "/".
 * @method static string         getApplicationRelativePath()                                              Return the request's path fragment relative to the application's base URL. This value always starts with a slash "/".
 * @method static string         getApplicationBaseUri()                                                   Return the application's base URI. The value always starts and ends with a slash "/".
 * @method static string         getQueryString()                                                          Return the query string of the request's URL.
 * @method static string         getRemoteAddress()                                                        Return the IP address the request was made from.
 * @method static string         getRemoteHostname()                                                       Return the name of the host the request was made from.
 * @method static string         getContent()                                                              Return the content of the request (the body). For file uploads the method doesn't return the real binary content. Instead it returns available metadata.
 * @method static ?string        getContentType()                                                          Return the "Content-Type" header of the request. If multiple "Content-Type" headers have been transmitted the first one is returned.
 * @method static HttpSession    getSession()                                                              Return the current HTTP session object. If a session object does not yet exist, one is created.
 * @method static bool           isSession()                                                               Whether an HTTP session was started during the request. Not whether the session is still open (active).
 * @method static bool           isSessionAttribute(string $key)                                           Whether a session attribute of the specified name exists. If no session exists none is started.
 * @method static string         getSessionId()                                                            Return the session id transmitted with the request (not the id sent with the response, which may differ).
 * @method static bool           hasSessionId()                                                            Whether a valid session id was transmitted with the request. An invalid id is a URL based session id when the php.ini setting "session.use_only_cookies" is enabled.
 * @method static void           destroySession()                                                          Destroy the current session and it's data.
 * @method static string[]       getHeaders(string ...$names)                                              Return the headers with the specified names as an associative array of header names/values.
 * @method static ?string        getHeaderValue(string $name)                                              Return the value of the header with the specified name.
 * @method static mixed          getAttribute(string $name)                                                Return a value stored in the request's variables context under the specified name.
 * @method static mixed[]        getAttributes()                                                           Return all values stored in the request's variables context.
 * @method static void           setAttribute(string $name, mixed $value)                                  Store a value in the request's variables context. May be used to transfer data from controllers or {@link \rosasurfer\ministruts\struts\Action}s * to views.
 * @method static void           removeAttributes(string ...$names)                                        Remove the variable(s) with the specified name(s) from the request's variables context.
 * @method static void           setCookie(string $name, string $value, int $expires=0, string $path=null) Send a cookie.
 * @method static bool           isUserInRole(string $role)                                                Whether the current web user owns the specified role.
 * @method static ?string        getActionMessage(string $key=null)                                        Return the stored ActionMessage for the specified key, or the first ActionMessage if no key was given.
 * @method static string[]       getActionMessages()                                                       Return all stored ActionMessages, including ActionErrors.
 * @method static bool           isActionMessage(string ...$keys)                                          Whether an ActionMessage exists for one of the specified keys, or for any key if no key was given.
 * @method static void           setActionMessage(string $key, string $message)                            Store an ActionMessage for the specified key.
 * @method static string[]       removeActionMessages(string ...$keys)                                     Remove the ActionMessage(s) with the specified key(s).
 * @method static ?string        getActionError(string $key=null)                                          Return the stored ActionError for the specified key, or the first ActionError if no key was given.
 * @method static string[]       getActionErrors()                                                         Return all stored ActionErrors.
 * @method static bool           isActionError(string ...$keys)                                            Whether an ActionError exists for one of the specified keys, or for any key if no key was given.
 * @method static void           setActionError(string $key, string $message)                              Store an ActionError for the specified key.
 * @method static string[]       removeActionErrors(string ...$keys)                                       Remove the ActionError(s) with the specified key(s).
 * @method static ?ActionMapping getMapping()                                                              Return the MiniStruts {@link \rosasurfer\ministruts\struts\ActionMapping} responsible for processing the current request.
 * @method static ?Module        getModule()                                                               Return the MiniStruts {@link \\rosasurfer\ministruts\struts\Module} the current request is assigned to.
 */
class Request extends Proxy {

    /**
     * {@inheritDoc}
     */
    protected static function getServiceName(): string {
        return 'request';
        return StrutsRequest::class;                        // @phpstan-ignore deadCode.unreachable (keep for testing)
    }
}
