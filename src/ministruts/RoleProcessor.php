<?php
namespace rosasurfer\ministruts;


/**
 * RoleProcessor
 */
abstract class RoleProcessor {


    /**
     * Whether the current user owns the specified role(s).
     *
     * @param  Request $request
     * @param  string  $roles - one or more role identifier
     *
     * @return bool
     */
    abstract public function isUserInRole(Request $request, $roles);


    /**
     * Whether the current user owns the role(s) specified by the given {@link ActionMapping}.
     *
     * If the user owns the role(s) and access is granted the method returns NULL. If access is denied the method returns an
     * {@link ActionForward} pointing to the resource the user should be directed to.
     *
     * @param  Request       $request
     * @param  ActionMapping $mapping
     *
     * @return ActionForward|null
     */
    abstract public function processRoles(Request $request, ActionMapping $mapping);
}
