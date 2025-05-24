<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\struts;

use rosasurfer\ministruts\core\StaticClass;

/**
 * A class holding Struts related constants.
 */
final class Struts extends StaticClass {

    /**
     * Request or session key under which action messages are stored (if any).
     */
    public const ACTION_MESSAGES_KEY = 'org.apache.struts.action.MESSAGES';

    /**
     * Request or session key under which action errors are stored (if any).
     */
    public const ACTION_ERRORS_KEY = 'org.apache.struts.action.ERRORS';

    /**
     * Request or session key under which the current request's {@link ActionForm} is stored.
     */
    public const ACTION_FORM_KEY = 'org.apache.struts.action.FORM';

    /**
     * Request or session key under which the previous request's {@link ActionInput} is stored.
     */
    public const ACTION_INPUT_KEY = 'org.apache.struts.action.INPUT';

    /**
     * Request key under which the current request's {@link ActionMapping} is stored.
     */
    public const ACTION_MAPPING_KEY = 'org.apache.struts.action.MAPPING';

    /**
     * Session key under which the currently selected locale is stored.
     */
    public const LOCALE_KEY = 'org.apache.struts.action.LOCALE';

    /**
     * Request key under which available message resources are stored (i18n).
     */
    public const MESSAGES_KEY = 'org.apache.struts.action.MESSAGE_RESOURCES';

    /**
     * Request key under which the current request's {@link Module} is stored.
     */
    public const MODULE_KEY = 'org.apache.struts.action.MODULE';


    /**
     * Helper to throw a Struts configuration exception.
     *
     * @param  string $message
     *
     * @return never
     */
    public static function configError(string $message): void {
        throw new StrutsException("Struts config error: $message");
    }
}
