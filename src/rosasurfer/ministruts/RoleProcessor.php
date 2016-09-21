<?php
namespace rosasurfer\ministruts;


/**
 * RoleProcessor
 */
abstract class RoleProcessor {


   /**
    * Ob der aktuelle User Inhaber der angegebenen Rolle(n) ist.
    *
    * @param  Request $request
    * @param  string  $roles   - Rollenbezeichner
    *
    * @return bool
    */
   abstract public function isUserInRole(Request $request, $roles);


   /**
    * Ob der aktuelle User Inhaber der definierten Rolle(n) des angegebenen Mappings ist.  Gibt NULL
    * zurück, wenn die Verarbeitung fortgesetzt und der Zugriff gewährt, oder eine ActionForward-
    * Instanz, wenn der Zugriff nicht gewährt und statt dessen zu dem vom Forward beschriebenen Ziel
    * verzweigt werden soll.
    *
    * @param  Request       $request
    * @param  ActionMapping $mapping
    *
    * @return ActionForward oder NULL
    */
   abstract public function processRoles(Request $request, ActionMapping $mapping);
}
