<?php

use Bonfire\View\Component;

class FamousQuotesComponent extends Component
{
    public function __construct($attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function render(): string
    {
        return $this->renderView($this->view, $this->getFamousQuote());
    }

    public function getFamousQuote($seconds = 5): array
    {
        helper('cache');

        if (isset($this->attributes['seconds']) && is_numeric($this->attributes['seconds'])) {
            $seconds = (int) round($this->attributes['seconds'], 0);
        }

        $this->attributes['seconds'] ?? $seconds;

        // Define a cache key
        $cacheKey = 'famous_quote';

        // Try to get the cached quote
        if ($cachedQuote = cache($cacheKey)) {
            return $cachedQuote;
        }

        // If not cached, fetch from the API
        $url = 'https://zenquotes.io/api/random';
        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if (isset($data[0])) {
            $quote = [
                'seconds' => $seconds,
                'quote'   => $data[0]['q'],
                'author'  => $data[0]['a']
            ];

            // Cache the quote for 60 seconds
            cache()->save($cacheKey, $quote, $seconds);

            return $quote;
        }

        // Default quote if API fails
        $defaultQuote = [
            'quote' => 'The only way to do great work is to love what you do',
            'author' => 'Steve Jobs'
        ];

        cache()->save($cacheKey, $defaultQuote, $seconds);

        return $defaultQuote;
    }
}
