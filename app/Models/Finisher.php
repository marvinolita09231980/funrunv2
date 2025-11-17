<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Finisher extends Model
{
    protected $table = 'finishers'; // optional if table is 'finishers'
    protected $fillable = [
        'participantNumber',
        'firstName',
        'lastName',
        'middleInitial',
        'categoryDescription',
        'subDescription',
        'gender',
        'distanceCategory',
        'racebib',
        'guntime',
        'finisher_rank',
    ];
}
