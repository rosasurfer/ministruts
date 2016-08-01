<?php
namespace rosasurfer\acl;

use rosasurfer\core\Object;


/**
 * A Role organizes activity. It has a unique name and can hold a set of Policies and other Roles.
 * If no custom Role class is configured this class is the default implementation.
 */
class Role extends Object implements RoleInterface {
}
