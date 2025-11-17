<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Finisher extends Model
{
    protected $table = 'finishers'; // optional if table is 'finishers'
    protected $fillable = [
        'participants_id',
        'firstName',
        'lastName',
        'middleInitial',
        'categoryDescription',
        'subDescription',
        'gender',
        'birthDate',
        'distanceCategory',
        'racebib',
        'finish_time',
        'created_by',
        'finisher_rank',
    ];
     
    public function getRankAttribute()
    {
        // Get all finishers for this distance
        $finishers = self::where('distanceCategory', $this->distanceCategory)
            ->whereNotNull('finish_time')
            ->orderBy('finish_time', 'asc')
            ->pluck('id')
            ->toArray();

        $position = array_search($this->id, $finishers);

        return $position !== false ? $position + 1 : null;
    }

}
