<?php

namespace App\Models;

use System\BaseModel;

class UserModel extends BaseModel
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    
    // Add validation rules
    protected $validationRules = [
        'name' => 'required|min_length[3]',
        'email' => 'required|valid_email',
        'password' => 'required|min_length[6]',
    ];
}