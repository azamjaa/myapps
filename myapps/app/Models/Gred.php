<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gred extends Model
{
    use HasFactory;

    protected $table = 'gred';
    protected $primaryKey = 'id_gred';
    public $timestamps = false;

    protected $fillable = [
        'gred',
    ];

    protected $casts = [
        'id_gred' => 'integer',
    ];

    // Relationships
    public function staf()
    {
        return $this->hasMany(Staf::class, 'id_gred');
    }
}

