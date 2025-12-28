<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jawatan extends Model
{
    use HasFactory;

    protected $table = 'jawatan';
    protected $primaryKey = 'id_jawatan';
    public $timestamps = false;

    protected $fillable = [
        'jawatan',
        'skim',
    ];

    protected $casts = [
        'id_jawatan' => 'integer',
    ];

    // Relationships
    public function staf()
    {
        return $this->hasMany(Staf::class, 'id_jawatan');
    }

    public function getFullNameAttribute()
    {
        return $this->jawatan . ($this->skim ? " ({$this->skim})" : '');
    }
}

