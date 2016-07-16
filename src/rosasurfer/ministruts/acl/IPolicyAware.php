<?php
namespace rosasurfer\ministruts\acl;


/**
 * Interface implemented by access requesting Policy holders (subjects).
 */
interface IPolicyAware extends IIdentityNode {


   /**
    * Return the name of the identity.
    *
    * @return string
    */
   public function getIdentityName();


   /**
    * Return the ID of the identity.
    *
    * @return int
    */
   public function getIdentityId();
}
