<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\struts;


/**
 * DispatchAction
 *
 * A DispatchAction differs from a regular {@link Action} in that it automatically dispatches a request to a user-defined
 * method of the action. If no dispatch action key is submitted or a method matching the dispatch action key is not found
 * the DispatchAction falls back to the standard Action behavior.
 */
class DispatchAction extends Action {


    /**
     * Fall-back {@link Action} method called if no dispatch action key is submitted or a method matching the dispatch
     * action key is not found.
     *
     * By default the action returns a redirect to the root URI of the application. To change the default behavior implement
     * an application specific base DispatchAction which your concrete dispatch actions inherit from.
     */
    public function execute(Request $request, Response $response) {
        return new ActionForward('generic', $request->getApplicationUrl(), true);
    }
}
