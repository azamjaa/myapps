<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;

    protected $table = 'status';
    protected $primaryKey = 'id_status';
    public $timestamps = false;

    protected $fillable = [
        'status',
    ];

    protected $casts = [
        'id_status' => 'integer',
    ];

    // Relationships
    public function staf()
    {
        return $this->hasMany(Staf::class, 'id_status');
    }
}

