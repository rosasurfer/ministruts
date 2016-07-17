<?php
namespace rosasurfer\ministruts\acl;


/**
 * Interface implemented by ACL identities. Identitites are organized in a multi-root-node hierarchy. Each identity
 * can have multiple super-identities (parent nodes) and multiple sub-identities (child nodes). If an identity has no
 * parent nodes it is a root node identity. If an identity has no child nodes it is an end node identity.
 */
interface IIdentityNode {


   /**
    * Return the parent nodes of the identity (if any).
    *
    * @return IIdentityNode[] - array of nodes or an empty value if the identity is a root node
    */
   public function getParentNodes();
}
