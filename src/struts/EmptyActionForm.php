<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\struts;


/**
 * EmptyActionForm
 *
 * An empty default implementation automatically instantiated and assigned to an {@link ActionMapping} if no custom
 * {@link ActionForm} is configured. Ensures that view helpers can always resolve an actual {@link ActionForm} instance,
 * and don't have to deal with potential NULL values. Property getters and array access of properties return NULL for all properties.
 */
final class EmptyActionForm extends ActionForm {


    /**
     * {@inheritDoc}
     *
     * Intentionally this implementation does nothing.
     */
    protected function populate(): void {
    }


    /**
     * {@inheritDoc}
     *
     * This implementation always returns FALSE.
     */
    public function validate(): bool {
        return false;
    }
}
