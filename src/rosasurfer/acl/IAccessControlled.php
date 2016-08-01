<?php
namespace rosasurfer\ministruts\acl;


/**
 * Interface implemented by access controlled resources (objects).
 */
interface IAccessControlled extends IIdentityNode {


   /**
    * Return the type of the resource, e.g. the class name "Order" or the alias "route".
    *
    * @return string
    */
   public function getResourceType();


   /**
    * Return the ID of the resource. Can be anything uniquely identifying all instances of its type.
    *
    * @return string
    */
   public function getResourceId();
}
