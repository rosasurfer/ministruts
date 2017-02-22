<?php
/**
 * Package constants
 */
namespace rosasurfer\ministruts;


// block re-includes
if (defined('rosasurfer\ministruts\DEFAULT_REQUEST_PROCESSOR_CLASS')) return;


/**
 * Default {@link RequestProcessor} class.
 *
 * @var string
 */
const DEFAULT_REQUEST_PROCESSOR_CLASS = RequestProcessor::class;


/**
 * Default {@link ActionForward} class.
 *
 * @var string
 */
const DEFAULT_ACTION_FORWARD_CLASS = ActionForward::class;


/**
 * Default {@link ActionMapping} class.
 *
 * @var string
 */
const DEFAULT_ACTION_MAPPING_CLASS = ActionMapping::class;


/**
 * Default {@link Tile} class.
 *
 * @var string
 */
const DEFAULT_TILES_CLASS = Tile::class;


/**
 * {@link Action} base class.
 *
 * @var string
 */
const ACTION_BASE_CLASS = Action::class;


/**
 * {@link ActionForm} base class.
 *
 * @var string
 */
const ACTION_FORM_BASE_CLASS = ActionForm::class;


/**
 * {@link RoleProcessor} base class.
 *
 * @var string
 */
const ROLE_PROCESSOR_BASE_CLASS = RoleProcessor::class;


/**
 * Request or session key where {@link ActionMessage}s are stored (if any).
 *
 * @var string
 */
const ACTION_MESSAGES_KEY = 'org.apache.struts.action.MESSAGES';


/**
 * Request or session key where {@link ActionError}s are stored (if any).
 *
 * @var string
 */
const ACTION_ERRORS_KEY = 'org.apache.struts.action.ERRORS';


/**
 * Request or session key where the current request's {@link ActionForm} is stored.
 *
 * @var string
 */
const ACTION_FORM_KEY = 'org.apache.struts.action.FORM';


/**
 * Request key where the current request's {@link ActionMapping} is stored.
 *
 * @var string
 */
const ACTION_MAPPING_KEY = 'org.apache.struts.action.MAPPING';


/**
 * Session key where the currently selected {@link Locale} is stored.
 *
 * @var string
 */
const LOCALE_KEY = 'org.apache.struts.action.LOCALE';


/**
 * Request key where available {@link MessageResource}s are stored (i18n).
 *
 * @var string -
 */
const MESSAGES_KEY = 'org.apache.struts.action.MESSAGE_RESOURCES';


/**
 * Request key where the current request's {@link Module} is stored.
 *
 * @var string
 */
const MODULE_KEY = 'org.apache.struts.action.MODULE';
