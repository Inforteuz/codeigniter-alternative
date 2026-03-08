<?php

namespace App\Models;

use System\BaseModel;

class TestModel extends BaseModel
{
    protected $table = 'tests';
    protected $primaryKey = 'id';
    
    // Add validation rules
    protected $validationRules = [];
}