<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\struts;

use rosasurfer\ministruts\core\CObject;

/**
 * Action
 *
 * An Action contains the logic to execute a specific request and represents the interface to the business layer.
 */
abstract class Action extends CObject {

    /** @var ActionMapping - encapsulates a single routing configuration */
    protected ActionMapping $mapping;

    /** @var ActionForm - holds interpreted user input of the current request */
    protected ActionForm $form;


    /**
     * Constructor
     *
     * @param  ActionMapping $mapping - the mapping using the Action
     * @param  ActionForm    $form    - user input of the request
     */
    public function __construct(ActionMapping $mapping, ActionForm $form) {
        $this->mapping = $mapping;
        $this->form    = $form;
    }


    /**
     * Return the {@link ActionMapping} using the Action.
     *
     * @return ActionMapping
     */
    public function getMapping(): ActionMapping {
        return $this->mapping;
    }


    /**
     * Optional execution pre-processing hook, to be overwritten if needed. Return NULL if request processing is to continue,
     * or an {@link ActionForward} if request processing is already finished and forward to the target described by the
     * forward. This default implementation does nothing.
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
     * Execute the Action and return an {@link ActionForward} describing the target to forward to.
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
     * e.g. committing of transactions or closing of network connections. This default implementation does nothing.
     *
     * Special care needs to be taken in regard to sending of additional headers because at the time of invocation request
     * processing may have been already finished.
     *
     * @param  Request        $request
     * @param  Response       $response
     * @param  ?ActionForward $forward [optional] - original ActionForward as returned by Action::execute()
     *
     * @return ?ActionForward - original or modified ActionForward (e.g. a route with added query parameters)
     */
    public function executeAfter(Request $request, Response $response, ?ActionForward $forward = null): ?ActionForward {
        return $forward;
    }


    /**
     * Find and return the {@link ActionForward} with the specified name.
     *
     * @param  string $name - forward identifier
     *
     * @return ?ActionForward - found ActionForward or NULL if no such forward was found
     */
    protected function findForward(string $name): ?ActionForward {
        return $this->mapping->findForward($name);
    }
}
