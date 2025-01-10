<?php

use Bonfire\View\Component;

class FamousQuotesComponent extends Component
{
    protected $defaultNoOfSeconds = 5;

    protected string $famousQuotesAPINode = 'https://zenquotes.io/api/random';

    public function render(): string
    {
        return $this->renderView($this->view, $this->getFamousQuote());
    }

    public function getFamousQuote(): array
    {
        helper('cache');

        if (isset($this->data['seconds']) && is_numeric($this->data['seconds'])) {
            $this->data['seconds'] = (int) round($this->data['seconds'], 0);
        } else {
            $this->data['seconds'] = $this->defaultNoOfSeconds;
        }

        // Define a cache key
        $cacheKey = 'famous_quote';

        // Try to get the cached quote
        if ($cachedQuote = cache($cacheKey)) {
            return $cachedQuote;
        }

        // If not cached, fetch from the API
        $response = file_get_contents($this->famousQuotesAPINode);
        $quoteData = json_decode($response, true);

        if (isset($quoteData[0])) {
            $this->data['quote'] = [
                'text'    => $quoteData[0]['q'],
                'author'  => $quoteData[0]['a']
            ];

            // Cache the quote for 60 seconds
            cache()->save($cacheKey, $this->data, $this->data['seconds']);
        } else {
            // Default quote if API fails
            $this->data['quote'] = [
                'text' => 'The only way to do great work is to love what you do',
                'author' => 'Steve Jobs'
            ];
        }

        return $this->data;
    }
}