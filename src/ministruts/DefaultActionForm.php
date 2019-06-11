<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\proxy\Form;


/**
 * DefaultActionForm
 *
 * An empty default implementation which is instantiated and used if no custom {@link ActionForm} is configured for an
 * {@link ActionMapping}. Makes sure that the {@link Form} proxy can always resolve an actual {@link ActionForm} instance.
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
