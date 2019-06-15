<?php
namespace rosasurfer\ministruts;


/**
 * EmptyActionForm
 *
 * An empty default implementation automatically instantiated and assigned to an {@link ActionMapping} if no custom
 * {@link ActionForm} is configured. Ensures that view helpers can always resolve an actual {@link ActionForm} instance,
 * and don't have to deal with potential NULL pointer exceptions. Property getter {@link ActionForm::get()} and property
 * array access return NULL for all properties.
 */
final class EmptyActionForm extends ActionForm {


    /**
     * This implementation does nothing.
     *
     * {@inheritdoc}
     */
    protected function populate() {
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
