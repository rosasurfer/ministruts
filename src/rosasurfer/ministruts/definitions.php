<?php
namespace rosasurfer\ministruts;


/**
 * @var string - Der Klassenname der Default-RequestProcessor-Implementierung.
 */
const DEFAULT_REQUEST_PROCESSOR_CLASS = 'RequestProcessor';


/**
 * @var string - Der Klassenname der Default-ActionForward-Implementierung.
 */
const DEFAULT_ACTION_FORWARD_CLASS = 'ActionForward';


/**
 * @var string - Der Klassenname der Default-ActionMapping-Implementierung.
 */
const DEFAULT_ACTION_MAPPING_CLASS = 'ActionMapping';


/**
 * @var string - Der Klassenname der Default-Tiles-Implementierung.
 */
const DEFAULT_TILES_CLASS = 'Tile';


/**
 * @var string - Der Basisklassenname der Action-Implementierung.
 */
const ACTION_BASE_CLASS = 'Action';


/**
 * @var string - Der Basisklassenname der ActionForm-Implementierung.
 */
const ACTION_FORM_BASE_CLASS = 'ActionForm';


/**
 * @var string - Der Basisklassenname der RoleProcessor-Implementierung.
 */
const ROLE_PROCESSOR_BASE_CLASS = 'RoleProcessor';


/**
 * @var string - Request- oder Session-Key, unter dem eventuelle ActionErrors gespeichert sind.
 */
const ACTION_ERRORS_KEY = 'org.apache.struts.action.ERRORS';


/**
 * @var string - Request- oder Session-Key, unter dem die aktuelle ActionForm-Instanz gespeichert ist.
 */
const ACTION_FORM_KEY = 'org.apache.struts.action.FORM';


/**
 * @var string - Request-Key, unter dem das aktuelle ActionMapping gespeichert ist.
 */
const ACTION_MAPPING_KEY = 'org.apache.struts.action.MAPPING';


/**
 * @var string - Request- oder Session-Key, unter dem eventuelle ActionMessages gespeichert sind.
 */
const ACTION_MESSAGES_KEY = 'org.apache.struts.action.MESSAGES';


/**
 * @var string - Session-Key, unter dem ein vom User gewähltes Locale gespeichert ist.
 */
const LOCALE_KEY = 'org.apache.struts.action.LOCALE';


/**
 * @var string - Request-Key, unter dem die verfügbaren MessageResources gespeichert sind (für Internationalisierung).
 */
const MESSAGES_KEY = 'org.apache.struts.action.MESSAGE_RESOURCES';


/**
 * @var string - Request-Key, unter dem das diesem Request zugeordnete Modul gespeichert ist.
 */
const MODULE_KEY = 'org.apache.struts.action.MODULE';
