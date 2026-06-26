<?php

declare(strict_types=1);

namespace App\Form\Error;

use Laminas\Form\Element\Radio;
use Laminas\Form\Form;

class FormLinkedErrors
{
    public function fromForm(Form $form): array
    {
        $out = [];

        foreach ($form->getMessages() as $field => $errors) {
            $anchorId = (string) $field;

            if ($form->has((string) $field)) {
                $element = $form->get((string) $field);
                if ($element instanceof Radio) {
                    $valueOptions = $element->getValueOptions();
                    if (!empty($valueOptions)) {
                        $firstOption = reset($valueOptions);
                        $firstValue  = is_array($firstOption)
                            ? ($firstOption['value'] ?? (string) key($valueOptions))
                            : (string) key($valueOptions);
                        $anchorId = $field . '-' . $firstValue;
                    }
                }
            }

            foreach ((array) $errors as $error) {
                if (is_array($error)) {
                    foreach ($error as $subError) {
                        $this->pushIfString($out, $anchorId, $subError);
                    }
                    continue;
                }

                $this->pushIfString($out, $anchorId, $error);
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
