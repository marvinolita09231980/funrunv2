<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subcategory extends Model
{
     use HasFactory;
    protected $fillable = [
        'categoryDescription',
        'nop',
        'subDescription',
    ];

}
