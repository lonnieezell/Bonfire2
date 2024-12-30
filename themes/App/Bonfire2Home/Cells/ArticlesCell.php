<?php

namespace App\Modules\Bonfire2Home\Cells;

use CodeIgniter\View\Cells\Cell;

class ArticlesCell extends Cell
{
    public function render(): string
    {
        try {
            // check if the code we are actually using is present
            if (!class_exists(\App\Modules\Pages\Models\PagesModel::class)) {
                throw new \RuntimeException('PagesModel class not found');
            }

            $article = model(\App\Modules\Bonfire2Home\Models\PagesModel::class)->getRandomArticle();

            return view('\App\Modules\Bonfire2Home\Views\Cells\articles.php', [
                'articleTitle' => $article->title,
                'articleExcerpt' => $article->excerpt,
                'articleDate' => $article->date,
                'articleCategory' => $article->category,
            ]);
        } catch (\Exception $e) {
            // Serve static content if PagesModel is missing or any other exception occurs
            return view('\App\Modules\Bonfire2Home\Views\Cells\articles.php', [
                'articleTitle' => 'The Pages Module is missing',
                'articleExcerpt' => 'This text should come from the Pages module of Bonfire2, but the module is not installed, so static content is served. Go to <a href="https://github.com/dgvirtual/bonfire2-pages-module">Github</a> to learn how to install it.',
                'articleDate' => date('Y-m-d'),
                'articleCategory' => 'Missing',
            ]);
        }
    }
}
