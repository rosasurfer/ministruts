<?php
declare(strict_types=1);

/**
 * Package constants
 */
namespace rosasurfer\ministruts\struts;


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
 * Request or session key under which action messages are stored (if any).
 */
const ACTION_MESSAGES_KEY = 'org.apache.struts.action.MESSAGES';

/**
 * Request or session key under which action errors are stored (if any).
 */
const ACTION_ERRORS_KEY = 'org.apache.struts.action.ERRORS';

/**
 * Request or session key under which the current request's {@link ActionForm} is stored.
 */
const ACTION_FORM_KEY = 'org.apache.struts.action.FORM';

/**
 * Request or session key under which the previous request's {@link ActionInput} is stored.
 */
const ACTION_INPUT_KEY = 'org.apache.struts.action.INPUT';

/**
 * Request key under which the current request's {@link ActionMapping} is stored.
 */
const ACTION_MAPPING_KEY = 'org.apache.struts.action.MAPPING';

/**
 * Session key under which the currently selected locale is stored.
 */
const LOCALE_KEY = 'org.apache.struts.action.LOCALE';

/**
 * Request key under which available message resources are stored (i18n).
 */
const MESSAGES_KEY = 'org.apache.struts.action.MESSAGE_RESOURCES';

/**
 * Request key under which the current request's {@link Module} is stored.
 */
const MODULE_KEY = 'org.apache.struts.action.MODULE';
