<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Staf extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'staf';
    protected $primaryKey = 'id_staf';
    public $timestamps = false;

    protected $fillable = [
        'no_staf',
        'no_kp',
        'nama',
        'gambar',
        'emel',
        'telefon',
        'id_jawatan',
        'id_gred',
        'id_bahagian',
        'id_status',
        'remember_token',
        'email_verified_at',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'id_staf' => 'integer',
        'id_jawatan' => 'integer',
        'id_gred' => 'integer',
        'id_bahagian' => 'integer',
        'id_status' => 'integer',
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the name of the unique identifier for authentication
     * Using no_kp (IC Number) instead of id_staf
     */
    public function getAuthIdentifierName()
    {
        return 'no_kp';
    }

    /**
     * Get the column name for the "email" field
     */
    public function getEmailForPasswordReset()
    {
        return $this->emel;
    }

    // Relationships
    public function jawatan()
    {
        return $this->belongsTo(Jawatan::class, 'id_jawatan');
    }

    public function gred()
    {
        return $this->belongsTo(Gred::class, 'id_gred');
    }

    public function bahagian()
    {
        return $this->belongsTo(Bahagian::class, 'id_bahagian');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'id_status');
    }

    public function akses()
    {
        return $this->hasMany(Akses::class, 'id_staf');
    }

    public function audit()
    {
        return $this->hasMany(Audit::class, 'id_pengguna');
    }

    public function loginRecord()
    {
        return $this->hasOne(Login::class, 'id_staf', 'id_staf');
    }

    public function loginHistory()
    {
        return $this->hasMany(Login::class, 'id_staf', 'id_staf');
    }

    /**
     * Get the password for authentication from login table ONLY
     */
    public function getAuthPassword()
    {
        // MUST load loginRecord relationship
        if (!$this->relationLoaded('loginRecord')) {
            $this->load('loginRecord');
        }
        
        // Get password from login table ONLY
        if ($this->loginRecord && $this->loginRecord->password_hash) {
            return $this->loginRecord->password_hash;
        }
        
        // If no login record, return null (cannot authenticate)
        return null;
    }
    
    /**
     * Override password attribute to use login table
     */
    public function getPasswordAttribute()
    {
        return $this->getAuthPassword();
    }
    
    /**
     * Override to get password attribute
     */
    public function getAuthPasswordName()
    {
        return 'password';
    }

    // Accessors
    public function getEmailAttribute()
    {
        return $this->emel;
    }

    public function getNameAttribute()
    {
        return $this->nama;
    }

    public function getGambarUrlAttribute()
    {
        if ($this->gambar) {
            return asset('storage/gambar/' . $this->gambar);
        }
        return asset('images/default-avatar.png');
    }

    // Helper methods
    public function isActive()
    {
        return $this->id_status == 1;
    }

    // Birthday helper
    public function isBirthdayThisMonth()
    {
        if (!$this->no_kp || strlen($this->no_kp) < 6) {
            return false;
        }
        
        // Extract month from IC (format: YYMMDD)
        $icMonth = substr($this->no_kp, 2, 2);
        $currentMonth = date('m');
        
        return $icMonth == $currentMonth;
    }

    public function getBirthdayAttribute()
    {
        if (!$this->no_kp || strlen($this->no_kp) < 6) {
            return null;
        }
        
        $year = substr($this->no_kp, 0, 2);
        $month = substr($this->no_kp, 2, 2);
        $day = substr($this->no_kp, 4, 2);
        
        // Determine century
        $fullYear = ((int)$year > 30) ? '19' . $year : '20' . $year;
        
        return "$fullYear-$month-$day";
    }

    public function getAgeAttribute()
    {
        $birthday = $this->birthday;
        if (!$birthday) {
            return null;
        }
        
        return \Carbon\Carbon::parse($birthday)->age;
    }
}

