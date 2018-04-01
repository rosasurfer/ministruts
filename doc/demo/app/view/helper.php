<?php
/**
 * Map helpers to the project's view namespace to save additional imports in the HTML files.
 */
namespace rosasurfer\ministruts\demo\view;

use rosasurfer\ministruts\Module;
use rosasurfer\ministruts\url\VersionedUrl;


/**
 * The purpose of these two definitions is to enable IDE code completion for it. The constants are in fact
 * defined at runtime in the web framework (where IDEs cannot see them).
 */
if (false) {
    define('APP',    'runtime generated application URI (not ending with a slash)');
    define('MODULE', 'runtime generated module URI (not ending with a slash)'     );
}


/**
 * Return a version-aware URL helper for the given URI {@link VersionedUrl}. An URI starting with a slash "/" is interpreted
 * as relative to the application's base URI. An URI not starting with a slash is interpreted as relative to the application
 * {@link Module}'s base URI (the module the current request belongs to).<br>
 *
 * @param  string $uri
 *
 * @return VersionedUrl
 */
function asset($uri) {
    return \rosasurfer\asset($uri);
}


/**
 * Prints a variable in a pretty way. Output always ends with a line feed.
 *
 * @param  mixed $var
 * @param  bool  $flushBuffers [optional] - whether or not to flush output buffers (default: TRUE)
 */
function echoPre($var, $flushBuffers = true) {
    return \rosasurfer\echoPre($var, $flushBuffers);
}
