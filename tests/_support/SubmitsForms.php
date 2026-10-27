<?php

namespace Tests\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Reads a rendered form the way a browser submits it, so a test can post
 * exactly what the page would: checked boxes and radios, selected options,
 * and no disabled fields.
 */
trait SubmitsForms
{
    /**
     * @return array<string, mixed>
     */
    protected function formFields(string $html, string $action): array
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $form  = $xpath->query('//form[@action="' . $action . '"]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $form, "No form posts to {$action}");

        $pairs = [];

        foreach ($xpath->query('.//input | .//select | .//textarea', $form) as $field) {
            /** @var DOMElement $field */
            $name = $field->getAttribute('name');
            $type = strtolower($field->getAttribute('type'));

            if ($name === '' || $field->hasAttribute('disabled') || in_array($type, ['submit', 'button', 'file'], true)) {
                continue;
            }

            if ($field->tagName === 'select') {
                $options  = $xpath->query('.//option', $field);
                $selected = $xpath->query('.//option[@selected]', $field)->item(0) ?? $options->item(0);

                if ($selected !== null) {
                    $pairs[] = [$name, $selected->hasAttribute('value') ? $selected->getAttribute('value') : trim($selected->textContent)];
                }
            } elseif ($field->tagName === 'textarea') {
                $pairs[] = [$name, $field->textContent];
            } elseif (! in_array($type, ['checkbox', 'radio'], true) || $field->hasAttribute('checked')) {
                $default = in_array($type, ['checkbox', 'radio'], true) ? 'on' : '';
                $pairs[] = [$name, $field->hasAttribute('value') ? $field->getAttribute('value') : $default];
            }
        }

        parse_str(implode('&', array_map(static fn (array $pair): string => urlencode($pair[0]) . '=' . urlencode($pair[1]), $pairs)), $fields);

        return $fields;
    }
}
