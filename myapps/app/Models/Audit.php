<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    use HasFactory;

    protected $table = 'audit';
    protected $primaryKey = 'id_audit';
    public $timestamps = false;

    protected $fillable = [
        'waktu',
        'id_pengguna',
        'tindakan',
        'nama_jadual',
        'id_rekod',
        'data_lama',
        'data_baru',
    ];

    protected $casts = [
        'id_audit' => 'integer',
        'id_pengguna' => 'integer',
        'id_rekod' => 'integer',
        'waktu' => 'datetime',
        'data_lama' => 'array',
        'data_baru' => 'array',
    ];

    // Relationships
    public function pengguna()
    {
        return $this->belongsTo(Staf::class, 'id_pengguna');
    }

    // Helper methods
    public function getChangesAttribute()
    {
        $changes = [];
        
        if (!$this->data_lama || !$this->data_baru) {
            return $changes;
        }

        $old = is_array($this->data_lama) ? $this->data_lama : json_decode($this->data_lama, true);
        $new = is_array($this->data_baru) ? $this->data_baru : json_decode($this->data_baru, true);

        if (!is_array($old) || !is_array($new)) {
            return $changes;
        }

        foreach ($new as $key => $value) {
            if (isset($old[$key]) && $old[$key] != $value) {
                $changes[] = [
                    'field' => $this->translateField($key),
                    'old' => $old[$key] ?? 'N/A',
                    'new' => $value ?? 'N/A',
                ];
            }
        }

        return $changes;
    }

    protected function translateField($field)
    {
        $translations = [
            'nama' => 'Nama',
            'emel' => 'Emel',
            'telefon' => 'No. Telefon',
            'no_kp' => 'No. KP',
            'no_staf' => 'No. Staf',
            'id_jawatan' => 'Jawatan',
            'id_gred' => 'Gred',
            'id_bahagian' => 'Bahagian',
            'id_status' => 'Status',
        ];

        return $translations[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    public function getHumanReadableDescriptionAttribute()
    {
        $description = '';
        
        switch ($this->tindakan) {
            case 'INSERT':
                $description = 'Rekod baru ditambah';
                break;
            case 'UPDATE':
                $changes = $this->changes;
                if (count($changes) > 0) {
                    $description = 'Dikemaskini: ' . implode(', ', array_column($changes, 'field'));
                } else {
                    $description = 'Rekod dikemaskini';
                }
                break;
            case 'DELETE':
                $description = 'Rekod dipadam';
                break;
            default:
                $description = $this->tindakan;
        }

        return $description;
    }

    // Scopes
    public function scopeForStaf($query, $idStaf)
    {
        return $query->where('nama_jadual', 'staf')
                     ->where('id_rekod', $idStaf)
                     ->orderBy('waktu', 'desc');
    }

    public function scopeByTable($query, $tableName)
    {
        return $query->where('nama_jadual', $tableName);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('tindakan', $action);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('waktu', '>=', now()->subDays($days));
    }
}

