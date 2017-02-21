<?php
namespace rosasurfer\acl;


/**
 * Interface implemented by access requesting Role holders (subjects).
 */
interface IRoleAware extends IIdentityNode {


    /**
     * Return the type of the identity, e.g. the class name "User".
     *
     * @return string
     */
    public function getIdentityType();


    /**
     * Return the ID of the identity. Can be anything uniquely identifying all instances of its type.
     *
     * @return string
     */
    public function getIdentityId();


    /**
     * Return the Roles the identity is holding.
     *
     * @return RoleInterface[]
     */
    public function getRoles();


    /**
     * Return the names of the Roles the identity is holding.
     *
     * @return string[]
     */
    public function getRoleNames();
}
