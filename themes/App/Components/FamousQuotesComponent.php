<?php

use Bonfire\View\Component;

class FamousQuotesComponent extends Component
{
    public function render(): string
    {
        return $this->renderView($this->view, $this->getFamousQuote());
    }

    public function getFamousQuote(): array
    {
        $url = 'https://zenquotes.io/api/random';
        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if (isset($data[0])) {
            return [
                'quote' => $data[0]['q'],
                'author' => $data[0]['a']
            ];
        }

        return [
            'quote' => 'The only way to do great work is to love what you do',
            'author' => 'Steve Jobs'
        ];
    }
}
