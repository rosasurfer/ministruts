<?php
namespace rosasurfer\ministruts\acl;


/**
 * Interface implemented by ACL identities. Identitites are organized in a multi-root-node hierarchy. Each identity
 * can have multiple super-identities (parent nodes) and multiple sub-identities (child nodes). If an identity has no
 * parent node it is a root node. If an identity has no child nodes it is an end node.
 */
interface IIdentityNode {


   /**
    * Return the name of the node, e.g. the class name (i.e. not unique).
    *
    * @return string
    */
   public function getNodeName();


   /**
    * Return the ID of the node. Can be anything uniquely identifying all nodes with the same name.
    *
    * @return string
    */
   public function getNodeId();


   /**
    * Return the parent nodes of the node (if any).
    *
    * @return IIdentityNode[] - array of nodes or an empty value if the node is a root node
    */
   public function getParentNodes();
}
