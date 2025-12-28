<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Akses extends Model
{
    use HasFactory;

    protected $table = 'akses';
    protected $primaryKey = 'id_akses';
    public $timestamps = false;

    protected $fillable = [
        'id_staf',
        'id_aplikasi',
        'id_level',
    ];

    protected $casts = [
        'id_akses' => 'integer',
        'id_staf' => 'integer',
        'id_aplikasi' => 'integer',
        'id_level' => 'integer',
    ];

    // Relationships
    public function staf()
    {
        return $this->belongsTo(Staf::class, 'id_staf');
    }

    public function aplikasi()
    {
        return $this->belongsTo(Aplikasi::class, 'id_aplikasi');
    }

    public function level()
    {
        return $this->belongsTo(Level::class, 'id_level');
    }
}

