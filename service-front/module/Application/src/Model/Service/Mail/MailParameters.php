<?php

namespace Application\Model\Service\Mail;

/**
 * Collects together the data required to send an email.
 * The "from"/"reply to" email address on the email is
 * currently not required by Notify
 * and is therefore not stored here.
 *
 * If other implementations of MailTransportInterface
 * require additional data, they should be added to this class.
 */
class MailParameters
{
    /**
     * Address(es) to send the email to.
     *
     * @var string|string[] $toAddresses
     */
    private $toAddresses;

    /**
     * Reference to a template. This will either be local
     * (e.g. Twig template name on disk)
     * or used by an external service (e.g. Notify template ID)
     * to locate the appropriate template.
     *
     * @var string $templateRef
     */
    private $templateRef;

    /**
     * Data to be interpolated into the template.
     *
     * @var array $data
     */
    private $data;

    /**
     * Constructor.
     *
     * @param string[]|string $toAddresses
     * @param string|null $templateRef
     * @param array $data
     */
    public function __construct(#[\SensitiveParameter] $toAddresses, ?string $templateRef = null, array $data = [])
    {
        if (!is_array($toAddresses)) {
            $toAddresses = [$toAddresses];
        }

        $this->toAddresses = $toAddresses;
        $this->templateRef = $templateRef;
        $this->data = $data;
    }

    public function getToAddresses()
    {
        return $this->toAddresses;
    }

    public function getTemplateRef()
    {
        return $this->templateRef;
    }

    public function getData()
    {
        return $this->data;
    }
}
