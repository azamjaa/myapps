<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    use HasFactory;

    protected $table = 'kategori';
    protected $primaryKey = 'id_kategori';
    public $timestamps = false;

    protected $fillable = [
        'nama_kategori',
        'deskripsi',
        'aktif',
        'tarikh_buat',
    ];

    protected $casts = [
        'id_kategori' => 'integer',
        'aktif' => 'boolean',
        'tarikh_buat' => 'datetime',
    ];

    // Relationships
    public function aplikasi()
    {
        return $this->hasMany(Aplikasi::class, 'id_kategori');
    }

    public function aplikasiAktif()
    {
        return $this->hasMany(Aplikasi::class, 'id_kategori')->where('status', 1);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('aktif', 1);
    }
}

