<?php

namespace App\Models;

use System\BaseModel;

class UserModel extends BaseModel
{
    protected $table = 'users';
    protected $primaryKey = 'id';

    /**
     * Columns allowed for BaseModel::search().
     * @var string[]
     */
    protected $searchable = ['name', 'email'];
}

