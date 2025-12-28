<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Login extends Model
{
    use HasFactory;

    protected $table = 'login';
    protected $primaryKey = 'id_login';
    public $timestamps = true;
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id_staf',
        'password_hash',
        'role',
        'otp_code',
        'otp_expiry',
        'reset_token',
        'reset_token_expiry',
        'waktu_login',
        'waktu_logout',
        'ip_address',
        'user_agent',
        'status',
        'tarikh_tukar_katalaluan',
    ];

    protected $casts = [
        'id_login' => 'integer',
        'id_staf' => 'integer',
        'waktu_login' => 'datetime',
        'waktu_logout' => 'datetime',
        'otp_expiry' => 'datetime',
        'reset_token_expiry' => 'datetime',
        'tarikh_tukar_katalaluan' => 'datetime',
    ];

    // Relationships
    public function staf()
    {
        return $this->belongsTo(Staf::class, 'id_staf', 'id_staf');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('waktu_logout');
    }

    public function scopeForStaf($query, $idStaf)
    {
        return $query->where('id_staf', $idStaf);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('waktu_login', '>=', now()->subDays($days));
    }
}

