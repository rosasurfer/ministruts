<?php
namespace rosasurfer\ministruts;


/**
 * @var string - default <RequestProcessor> class
 */
const DEFAULT_REQUEST_PROCESSOR_CLASS = 'RequestProcessor';


/**
 * @var string - default <ActionForward> class
 */
const DEFAULT_ACTION_FORWARD_CLASS = 'ActionForward';


/**
 * @var string - default <ActionMapping> class
 */
const DEFAULT_ACTION_MAPPING_CLASS = 'ActionMapping';


/**
 * @var string - default <Tile> class
 */
const DEFAULT_TILES_CLASS = 'Tile';


/**
 * @var string - <Action> base class
 */
const ACTION_BASE_CLASS = 'Action';


/**
 * @var string - <ActionForm> base class
 */
const ACTION_FORM_BASE_CLASS = 'ActionForm';


/**
 * @var string - <RoleProcessor> base class
 */
const ROLE_PROCESSOR_BASE_CLASS = 'RoleProcessor';


/**
 * @var string - request or session key where <ActionMessages> are stored (if any)
 */
const ACTION_MESSAGES_KEY = 'org.apache.struts.action.MESSAGES';


/**
 * @var string - request or session key where <ActionErrors> are stored (if any)
 */
const ACTION_ERRORS_KEY = 'org.apache.struts.action.ERRORS';


/**
 * @var string - request or session key where the current request's <ActionForm> is stored
 */
const ACTION_FORM_KEY = 'org.apache.struts.action.FORM';


/**
 * @var string - request key where the current request's <ActionMapping> is stored
 */
const ACTION_MAPPING_KEY = 'org.apache.struts.action.MAPPING';


/**
 * @var string - session key where the currently selected <Locale> is stored
 */
const LOCALE_KEY = 'org.apache.struts.action.LOCALE';


/**
 * @var string - request key where available <MessageResources> are stored (i18n)
 */
const MESSAGES_KEY = 'org.apache.struts.action.MESSAGE_RESOURCES';


/**
 * @var string - request key where the current request's <Module> is stored
 */
const MODULE_KEY = 'org.apache.struts.action.MODULE';


/**
 * Return a new URL instance.
 *
 * @return Url
 */
function url() {
   return new Url();
   /*
   function ksort_r(array $values, $sort_flags=SORT_REGULAR) {
      return call_user_func_array('rosasurfer\\'.__FUNCTION__, func_get_args());
   }
   */
}
