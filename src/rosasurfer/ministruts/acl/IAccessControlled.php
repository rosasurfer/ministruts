<?php
namespace rosasurfer\ministruts\acl;


/**
 * Interface implemented by access controlled resources (objects).
 */
interface IAccessControlled extends IIdentityNode {


   /**
    * Return the name of the resource.
    *
    * @return string
    */
   public function getResourceName();


   /**
    * Return the ID of the resource.
    *
    * @return int
    */
   public function getResourceId();
}
