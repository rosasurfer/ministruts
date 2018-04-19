<?php
namespace rosasurfer\ministruts;


/**
 * A DispatchAction differs from a regular {@link Action} in that it automatically dispatches a request to a user-defined
 * method of the action. If no dispatch action key is submitted or a method matching the dispatch action key is not found
 * the DispatchAction falls back to the standard Action behaviour.
 */
abstract class DispatchAction extends Action {
}
