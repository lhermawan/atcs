<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficLog extends Model
{
    protected $fillable = [
        'stream_id',
        'camera_name',
        'car_count',
        'motorcycle_count',
    ];
}
