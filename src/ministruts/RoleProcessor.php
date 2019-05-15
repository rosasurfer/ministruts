<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Object;


/**
 * RoleProcessor
 */
abstract class RoleProcessor extends Object {


    /**
     * Whether the rights of the current user satisfy the specified role constraint.
     *
     * @param  Request $request
     * @param  string  $role - role identifier or constraint (e.g. "admin" or "!admin")
     *
     * @return bool
     */
    abstract public function isUserInRole(Request $request, $roles);


    /**
     * Whether the rights of the current user satisfy the role constraints specified by the given {@link ActionMapping}.
     *
     * If access to the ActionMapping is granted the method returns NULL. If access is denied the method returns an
     * {@link ActionForward} pointing to the resource the user should be directed to.
     *
     * @param  Request       $request
     * @param  ActionMapping $mapping
     *
     * @return ActionForward|null
     */
    abstract public function processRoles(Request $request, ActionMapping $mapping);
}
