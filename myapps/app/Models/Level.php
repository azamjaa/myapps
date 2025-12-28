<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    use HasFactory;

    protected $table = 'level';
    protected $primaryKey = 'id_level';
    public $timestamps = false;

    protected $fillable = [
        'level',
        'deskripsi',
    ];

    protected $casts = [
        'id_level' => 'integer',
    ];

    // Relationships
    public function akses()
    {
        return $this->hasMany(Akses::class, 'id_level');
    }
}

