<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\struts;

use rosasurfer\ministruts\core\StaticClass;


/**
 * A class holding Struts related constants.
 */
final class Struts extends StaticClass {


    /**
     * Default {@link RequestProcessor} class.
     *
     * @var class-string<RequestProcessor>
     */
    const DEFAULT_REQUEST_PROCESSOR_CLASS = RequestProcessor::class;

    /**
     * Default {@link ActionForward} class.
     *
     * @var class-string<ActionForward>
     */
    const DEFAULT_ACTION_FORWARD_CLASS = ActionForward::class;

    /**
     * Default {@link ActionMapping} class.
     *
     * @var class-string<ActionMapping>
     */
    const DEFAULT_ACTION_MAPPING_CLASS = ActionMapping::class;

    /**
     * {@link Action} base class.
     *
     * @var class-string<Action>
     */
    const ACTION_BASE_CLASS = Action::class;

    /**
     * {@link ActionForm} base class.
     *
     * @var class-string<ActionForm>
     */
    const ACTION_FORM_BASE_CLASS = ActionForm::class;

    /**
     * {@link RoleProcessor} base class.
     *
     * @var class-string<RoleProcessor>
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


    /**
     * Helper to throw a Struts configuration exception.
     *
     * @param  string $message
     *
     * @return never
     */
    public static function configError(string $message) {
        throw new StrutsException("Struts config error: $message");
    }
}
