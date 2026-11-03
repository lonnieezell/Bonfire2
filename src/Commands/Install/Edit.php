<?php

namespace Bonfire\Commands\Install;

/**
 * The outcome of deciding an edit to a file's content, before anything is
 * written. `missing` lists the text the edit expected to find but did not,
 * so the installer can say so instead of reporting a success it did not do.
 */
final readonly class Edit
{
    /**
     * @param list<string> $missing
     */
    public function __construct(
        public string $original,
        public string $content,
        public array $missing = [],
    ) {
    }

    /**
     * Whether the content differs from the original and needs writing.
     */
    public function changed(): bool
    {
        return $this->content !== $this->original;
    }

    /**
     * Whether the edit found everything it expected, whether or not it
     * had to change anything (it may have been applied already).
     */
    public function complete(): bool
    {
        return $this->missing === [];
    }
}
