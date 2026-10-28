<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Dashboard;

/**
 * One setting a widget declares, which admins change for the widget as
 * placed on their own dashboard.
 *
 * A placement stores only the values that differ from the field's default.
 */
final readonly class WidgetField
{
    public const MAX_TEXT = 255;

    /**
     * @param array<string, string> $options Value => label, for a choice
     */
    private function __construct(
        public string $type,
        public string $name,
        public string $label,
        public bool|float|int|string $default,
        public ?string $description,
        public float|int|null $min = null,
        public float|int|null $max = null,
        public float|int $step = 1,
        public array $options = [],
    ) {
    }

    public static function toggle(string $name, string $label, bool $default, ?string $description = null): self
    {
        return new self('toggle', $name, $label, $default, $description);
    }

    /**
     * A whole number, unless the step is a fraction.
     */
    public static function number(string $name, string $label, float|int $default, float|int|null $min = null, float|int|null $max = null, float|int $step = 1, ?string $description = null): self
    {
        // Of the same type as a value, so one equal to the default is not stored
        $default = is_int($step) ? (int) round($default) : (float) $default;

        return new self('number', $name, $label, $default, $description, $min, $max, $step);
    }

    /**
     * @param array<string, string> $options Value => label
     */
    public static function choice(string $name, string $label, array $options, string $default, ?string $description = null): self
    {
        return new self('choice', $name, $label, $default, $description, options: $options);
    }

    public static function text(string $name, string $label, string $default, ?string $description = null): self
    {
        return new self('text', $name, $label, $default, $description);
    }

    /**
     * The value as this field's type: a number clamped to its range, text
     * trimmed. Null when it is not one, such as a choice it does not offer.
     */
    public function cast(mixed $value): bool|float|int|string|null
    {
        if (! is_scalar($value)) {
            return null;
        }

        return match ($this->type) {
            'toggle' => is_bool($value) ? $value : filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE),
            'number' => is_numeric($value) && is_finite((float) $value) ? $this->clamp((float) $value) : null,
            'choice' => array_key_exists((string) $value, $this->options) ? (string) $value : null,
            'text'   => is_string($value) ? mb_substr(trim($value), 0, self::MAX_TEXT) : null,
        };
    }

    private function clamp(float $value): float|int
    {
        $value = max($this->min ?? $value, min($this->max ?? $value, $value));

        return is_int($this->step) ? (int) round($value) : $value;
    }
}
