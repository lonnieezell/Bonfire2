<?php

namespace Tests\Support\Content;

use CodeIgniter\I18n\Time;
use CodeIgniter\Test\Mock\MockCache;

/**
 * The test cache, which keeps everything until it's cleaned, but forgets an item once its time to live has passed
 * by the clock Time::setTestNow() sets.
 */
class ExpiringCache extends MockCache
{
    public function get(string $key): mixed
    {
        $key = static::validateKey($key, $this->prefix);

        $expires = $this->expirations[$key] ?? null;

        if (is_int($expires) && $expires <= Time::now()->getTimestamp()) {
            unset($this->cache[$key], $this->expirations[$key]);
        }

        return $this->cache[$key] ?? null;
    }
}
