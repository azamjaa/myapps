<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aplikasi extends Model
{
    use HasFactory;

    protected $table = 'aplikasi';
    protected $primaryKey = 'id_aplikasi';
    public $timestamps = false;

    protected $fillable = [
        'id_kategori',
        'nama_aplikasi',
        'tarikh_daftar',
        'keterangan',
        'url',
        'warna_bg',
        'sso_comply',
        'status',
    ];

    protected $casts = [
        'id_aplikasi' => 'integer',
        'id_kategori' => 'integer',
        'sso_comply' => 'boolean',
        'status' => 'boolean',
        'tarikh_daftar' => 'datetime',
    ];

    // Relationships
    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'id_kategori');
    }

    public function akses()
    {
        return $this->hasMany(Akses::class, 'id_aplikasi');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeSsoCompliant($query)
    {
        return $query->where('sso_comply', 1);
    }

    public function scopeByKategori($query, $kategoriId)
    {
        return $query->where('id_kategori', $kategoriId);
    }

    // Helper methods
    public function isActive()
    {
        return $this->status == 1;
    }

    public function isSsoCompliant()
    {
        return $this->sso_comply == 1;
    }

    public function getSsoStatusBadgeAttribute()
    {
        return $this->sso_comply 
            ? '<span class="badge bg-success">SSO Compliant</span>' 
            : '<span class="badge bg-secondary">Non-SSO</span>';
    }
}

