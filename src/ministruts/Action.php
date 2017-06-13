<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Object;


/**
 * Action
 *
 * Contains logic to execute for a specific request and represents the interface to the business layer.
 */
abstract class Action extends Object {


    /** @var ActionMapping - encapsulates a single routing configuration */
    protected $mapping;

    /** @var ActionForm - holds user input of the current request */
    protected $form;


    /**
     * Constructor
     *
     * Create a new Action instance.
     *
     * @param  ActionMapping $mapping         - the mapping using the Action
     * @param  ActionForm    $form [optional] - user input of the request
     */
    public function __construct(ActionMapping $mapping, ActionForm $form = null) {
        $this->mapping = $mapping;
        $this->form    = $form;
    }


    /**
     * Return the {@link ActionMapping} using the Action.
     *
     * @return ActionMapping
     */
    public function getMapping() {
        return $this->mapping;
    }


    /**
     * Optional execution pre-processing hook, to be overwritten if needed. Return NULL if request processing is to continue,
     * or an ActionForward if request processing is already finished and forward to the target described by the forward.
     * The default implementation does nothing.
     *
     * @param  Request  $request
     * @param  Response $response
     *
     * @return ActionForward|string|null - NULL to continue request processing;
     *                                     ActionForward or forward name if request processing is finished
     */
    public function executeBefore(Request $request, Response $response) {
        return null;
    }


    /**
     * Execute the Action and return an ActionForward describing the target to forward to. Must be implemented.
     *
     * @param  Request  $request
     * @param  Response $response
     *
     * @return ActionForward|string|null - ActionForward or forward name to forward to;
     *                                     NULL if request processing is finished
     */
    abstract public function execute(Request $request, Response $response);


    /**
     * Optional execution post-processing hook, to be overwritten if needed. May be used to finalize/clean-up runtime state,
     * e.g. committing of transactions or closing of network connections. The default implementation does nothing.
     *
     * Special care has to be taken because at the time of invocation request processing may already have been finished and
     * all content may have been delivered.
     *
     * @param  Request       $request
     * @param  Response      $response
     * @param  ActionForward $forward [optional] - original ActionForward as returned by Action::execute()
     *
     * @return ActionForward|null - original or modified ActionForward (e.g. a route with added query parameters)
     */
    public function executeAfter(Request $request, Response $response, ActionForward $forward = null) {
        return $forward;
    }


    /**
     * Find and return the ActionForward with the specified name. Local ActionForwards (mapping forwards) are preferred over
     * global definitions.
     *
     * @param  string $name - forward identifier
     *
     * @return ActionForward|null - found ActionForward or NULL if no such forward was found
     */
    protected function findForward($name) {
        return $this->mapping->findForward($name);
    }
}
