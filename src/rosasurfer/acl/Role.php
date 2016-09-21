<?php
namespace rosasurfer\acl;

use rosasurfer\core\Object;


/**
 * A <tt>Role</tt> organizes activity. It has a unique name and can hold a set of <tt>Policy</tt>s and other
 * <tt>Role</tt>s. If no custom <tt>Role</tt> class is configured this class is the default implementation.
 */
class Role extends Object implements RoleInterface {
}
