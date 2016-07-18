<?php
namespace rosasurfer\ministruts\acl;


/**
 * Interface implemented by access requesting Policy holders (subjects).
 */
interface IPolicyAware extends IIdentityNode {


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
    * Return the Policies the identity is holding.
    *
    * @return IPolicy[]
    */
   public function getPolicies();
}
