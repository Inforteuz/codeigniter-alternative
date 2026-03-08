<?php

namespace App\Models;

use System\BaseModel;

class TaskModel extends BaseModel
{
    protected $table = 'tasks';
    protected $primaryKey = 'id';
    
    protected $allowedFields = [
        'user_id', 
        'title', 
        'description', 
        'status', 
        'created_at', 
        'updated_at'
    ];

    protected $validationRules = [
        'user_id' => 'required|numeric',
        'title'   => 'required|min_length[3]|max_length[255]',
        'status'  => 'required'
    ];
}
