<?php

namespace App\Modules\Bonfire2Home\Models;

use App\Modules\Pages\Models\PagesModel as BasePagesModel;

class PagesModel extends BasePagesModel
{
    /**
     * Returns a random article.
     *
     * @return \App\Modules\Pages\Entities\Page|null
     */
    public function getRandomArticle()
    {
        $dbDriver = $this->db->DBDriver;

        switch ($dbDriver) {
            case 'SQLSRV':
                $randomFunction = 'NEWID()';
                break;
            case 'OCI8':
                $randomFunction = 'DBMS_RANDOM.VALUE';
                break;
            case 'Postgre':
            case 'SQLite3':
                $randomFunction = 'RANDOM()';
                break;
            default:
                // Default to MySQL RAND() if the driver is not explicitly checked
                $randomFunction = 'RAND()';
                break;
        }

        return $this->select('id, title, excerpt, category, created_at')->orderBy($randomFunction)->first();
    }
}