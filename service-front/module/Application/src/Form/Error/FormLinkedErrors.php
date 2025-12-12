<?php

declare(strict_types=1);

namespace Application\Form\Error;

use Laminas\Form\Form;

class FormLinkedErrors
{
    public function fromForm(Form $form): array
    {
        $out = [];

        foreach ($form->getMessages() as $field => $errors) {
            foreach ((array) $errors as $error) {
                if (is_array($error)) {
                    foreach ($error as $subError) {
                        $this->pushIfString($out, (string) $field, $subError);
                    }
                    continue;
                }

                $this->pushIfString($out, (string) $field, $error);
            }
        }

        return $out;
    }

    private function pushIfString(array &$out, string $field, mixed $maybeString): void
    {
        if (is_string($maybeString) && $maybeString !== '') {
            $out[] = ['field' => $field, 'message' => $maybeString];
        }
    }
}
