<?php

declare(strict_types=1);

namespace App\View\Twig;

use Laminas\Form\FormInterface;

/**
 * Stub returned by the `form()` Twig function.
 * Provides openTag() and closeTag to render the HTML <form> element
 * using attributes from a Laminas FormInterface object.
 */
final class FormRendererStub
{
    /**
     * Renders the opening <form> tag using attributes from the form object.
     */
    public function openTag(FormInterface $form): string
    {
        $action = htmlspecialchars((string) ($form->getAttribute('action') ?? ''), ENT_QUOTES);
        $method = htmlspecialchars(strtolower((string) ($form->getAttribute('method') ?? 'post')), ENT_QUOTES);

        $extraAttrs = '';
        $skipAttrs  = ['action', 'method'];

        $formAttrs = $form->getAttributes();

        // Laminas Form::prepare() does not auto-set 'id' from 'name', but the legacy
        // view helper did. Many JS modules rely on selecting the form by id (e.g.
        // $("form#form-repeat-application")), so we mirror that legacy behaviour here:
        // if 'id' is absent but 'name' is present, promote 'name' to 'id'.
        if (!isset($formAttrs['id']) && isset($formAttrs['name'])) {
            $formAttrs['id'] = $formAttrs['name'];
        }

        foreach ($formAttrs as $name => $value) {
            if (in_array($name, $skipAttrs, true)) {
                continue;
            }

            $extraAttrs .= sprintf(
                ' %s="%s"',
                htmlspecialchars((string) $name, ENT_QUOTES),
                htmlspecialchars((string) $value, ENT_QUOTES)
            );
        }

        return sprintf('<form action="%s" method="%s"%s>', $action, $method, $extraAttrs);
    }

    /**
     * Renders the closing </form> tag.
     */
    public function getCloseTag(): string
    {
        return '</form>';
    }

    /**
     * Magic getter so `form().closeTag` works in Twig as a property access.
     */
    public function __get(string $name): string
    {
        if ($name === 'closeTag') {
            return $this->getCloseTag();
        }

        return '';
    }

    /**
     * Twig also resolves `form().closeTag()` as a method call before trying __get,
     * so we need closeTag() as a real method too.
     */
    public function closeTag(): string
    {
        return $this->getCloseTag();
    }
}
