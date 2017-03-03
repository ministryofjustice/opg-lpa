<?php

namespace Application\Form\View\Helper;

use Application\Form\Lpa\ReuseDetailsForm as ReuseForm;
use Zend\Form\View\Helper\AbstractHelper;
use Zend\Form\View\Helper\Form as FormHelper;

class ReuseDetailsForm extends AbstractHelper
{
    /**
     * Invoke as function
     *
     * @param   ReuseForm    $reuseDetailsForm
     * @param   string              $cancelUrl
     * @return  string
     */
    public function __invoke(ReuseForm $reuseDetailsForm, $cancelUrl = null)
    {
        return $this->render($reuseDetailsForm, $cancelUrl);
    }

    /**
     * Render the reuse details form
     *
     * @param   ReuseForm $reuseDetailsForm
     * @param   string                $cancelUrl
     * @return  string
     */
    public function render(ReuseForm $reuseDetailsForm, $cancelUrl = null)
    {
        //  Render the content for the form options
        $content = '';

        $currentUrl = $this->view->serverUrl(true);

        $isTrustView = (strpos($currentUrl, 'add-trust') !== false);

        $reuseDetailsForm->prepare();

        //  Add the required action, method and class attributes
        $reuseDetailsForm->setAttributes([
            'action' => $currentUrl,
            'method' => 'GET',
            'class'  => 'form reuse-details-form',
        ]);

        $reuseDetailsInput = $reuseDetailsForm->get('reuse-details');
        $reuseDetailsInput->setAttributes([
            'id' => 'reuse-details',
        ]);

        $reuseDetailsSubmit = $reuseDetailsForm->get('submit');
        $reuseDetailsSubmit->setAttributes([
            'value' => 'Continue',
            'class' => 'button flush--left reuse-details-button',
        ]);

        //  Extract the value options that can be used
        $reuseDetailsValueOptions = $reuseDetailsInput->getReuseDetailsValueOptions($isTrustView);

        if (count($reuseDetailsValueOptions) == 1) {
            //  Render the single option from the form as a link
            if (strpos($currentUrl, 'reuse-details') === false) {
                //  Construct the link target with an appropriate query joiner
                $reuseDetailsData = current($reuseDetailsValueOptions);
                $linkTarget = $currentUrl . (strpos($currentUrl, '?') === false ? '?' : '&') . 'reuse-details=' . $reuseDetailsData['value'];

                //  Construct the appropriate link text
                $linkText = 'Use my details';

                if ($isTrustView) {
                    $linkText = 'Use details of the ' . $reuseDetailsData['label'];
                }

                $content .= '<div class="use-details-link-panel hard--bottom">';
                $content .= '<a class="js-form-popup" href="' . $linkTarget . '">' . $linkText . '</a>';
                $content .= '</div>';
            }
        } elseif (count($reuseDetailsValueOptions) > 1) {
            //  Render the options as radio buttons in a form
            $formHelper = new FormHelper();

            $content .= $formHelper->openTag($reuseDetailsForm);
            $content .= '<fieldset class="reuse-details-container">';

            $content .= '<h2 class="heading-medium">Which person\'s details would you like to reuse?</h2>';

            $content .= '<div class="form-group">';
            $content .= $this->view->formElement($reuseDetailsInput);
            $content .= '</div>';

            $content .= '</fieldset>';

            $content .= $this->view->formElement($reuseDetailsSubmit);
            $content .= (is_string($cancelUrl) ? '<a href="' . $cancelUrl . '" class="button button-secondary js-cancel">Cancel</a>' : '');

            $content .= $formHelper->closeTag();
        }

        return $content;
    }
}
