<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMatoa extends Model
{
    use HasFactory;

    protected $connection = 'matoa';
    protected $table = 'users';
    protected $primaryKey = 'id';
}
