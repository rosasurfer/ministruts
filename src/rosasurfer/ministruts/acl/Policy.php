<?php
namespace rosasurfer\ministruts\acl;

use rosasurfer\ministruts\core\Object;


/**
 * A Policy defines an access rule for a task (specific operation on a specific access-controlled resource).
 * If no custom Policy class is configured this class is the default implementation.
 */
class Policy extends Object implements PolicyInterface {
}
