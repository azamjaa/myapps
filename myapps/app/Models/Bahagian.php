<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bahagian extends Model
{
    use HasFactory;

    protected $table = 'bahagian';
    protected $primaryKey = 'id_bahagian';
    public $timestamps = false;

    protected $fillable = [
        'bahagian',
    ];

    protected $casts = [
        'id_bahagian' => 'integer',
    ];

    // Relationships
    public function staf()
    {
        return $this->hasMany(Staf::class, 'id_bahagian');
    }

    public function stafAktif()
    {
        return $this->hasMany(Staf::class, 'id_bahagian')->where('id_status', 1);
    }
}

