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

        foreach ($form->getAttributes() as $name => $value) {
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
