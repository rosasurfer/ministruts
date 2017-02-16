<?php
namespace rosasurfer\ministruts;


/**
 * Package constants and functions.
 */
if (defined('rosasurfer\ministruts\DEFAULT_REQUEST_PROCESSOR_CLASS')) return;


/**
 * @var string - default <tt>RequestProcessor</tt> class
 */
const DEFAULT_REQUEST_PROCESSOR_CLASS = RequestProcessor::class;


/**
 * @var string - default <tt>ActionForward</tt> class
 */
const DEFAULT_ACTION_FORWARD_CLASS = ActionForward::class;


/**
 * @var string - default <tt>ActionMapping</tt> class
 */
const DEFAULT_ACTION_MAPPING_CLASS = ActionMapping::class;


/**
 * @var string - default <tt>Tile</tt> class
 */
const DEFAULT_TILES_CLASS = Tile::class;


/**
 * @var string - <tt>Action</tt> base class
 */
const ACTION_BASE_CLASS = Action::class;


/**
 * @var string - <tt>ActionForm</tt> base class
 */
const ACTION_FORM_BASE_CLASS = ActionForm::class;


/**
 * @var string - <tt>RoleProcessor</tt> base class
 */
const ROLE_PROCESSOR_BASE_CLASS = RoleProcessor::class;


/**
 * @var string - request or session key where <tt>ActionMessage</tt>s are stored (if any)
 */
const ACTION_MESSAGES_KEY = 'org.apache.struts.action.MESSAGES';


/**
 * @var string - request or session key where <tt>ActionError</tt>s are stored (if any)
 */
const ACTION_ERRORS_KEY = 'org.apache.struts.action.ERRORS';


/**
 * @var string - request or session key where the current request's <tt>ActionForm</tt> is stored
 */
const ACTION_FORM_KEY = 'org.apache.struts.action.FORM';


/**
 * @var string - request key where the current request's <tt>ActionMapping</tt> is stored
 */
const ACTION_MAPPING_KEY = 'org.apache.struts.action.MAPPING';


/**
 * @var string - session key where the currently selected <tt>Locale</tt> is stored
 */
const LOCALE_KEY = 'org.apache.struts.action.LOCALE';


/**
 * @var string - request key where available <tt>MessageResource</tt>s are stored (i18n)
 */
const MESSAGES_KEY = 'org.apache.struts.action.MESSAGE_RESOURCES';


/**
 * @var string - request key where the current request's <tt>Module</tt> is stored
 */
const MODULE_KEY = 'org.apache.struts.action.MODULE';
