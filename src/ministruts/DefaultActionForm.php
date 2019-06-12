<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\facade\Form;


/**
 * DefaultActionForm
 *
 * An empty default implementation which is instantiated and assigned to an {@link ActionMapping} if no custom
 * {@link ActionForm} is configured. Ensures that the {@link Form} facade always finds an actual {@link ActionForm} instance.
 * All parameter getters called on this class return NULL.
 */
final class DefaultActionForm extends ActionForm {


    /**
     * This implementation does nothing.
     *
     * {@inheritdoc}
     *
     * @param  Request $request
     */
    public function populate(Request $request) {
    }


    /**
     * This implementation always returns FALSE.
     *
     * {@inheritdoc}
     *
     * @return bool - whether the submitted parameters are valid
     */
    public function validate() {
        return false;
    }
}
