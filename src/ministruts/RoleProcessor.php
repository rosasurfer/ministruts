<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Object;


/**
 * RoleProcessor
 */
abstract class RoleProcessor extends Object {


    /**
     * Whether the current web user owns the specified role.
     *
     * @param  Request $request
     * @param  string  $role - a single role identifier (not an expression)
     *
     * @return bool
     */
    abstract public function isUserInRole(Request $request, $role);


    /**
     * Whether the rights of the current user satisfy the role constraints specified by the given {@link ActionMapping}.
     *
     * If access to the ActionMapping is granted the method returns NULL. If access is denied the method returns an
     * {@link ActionForward} pointing to the resource the user should be directed to.
     *
     * @param  Request       $request
     * @param  ActionMapping $mapping
     *
     * @return ActionForward|string|null - ActionForward instance or the name of a configured ActionForward
     */
    abstract public function processRoles(Request $request, ActionMapping $mapping);
}
