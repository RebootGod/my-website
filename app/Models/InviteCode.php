<?php

// ========================================
// 2. INVITE CODE MODEL
// ========================================
// File: app/Models/InviteCode.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InviteCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'status',
        'used_count',
        'max_uses',
        'created_by',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'max_uses' => 'integer',
        'used_count' => 'integer'
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function registrations()
    {
        return $this->hasMany(UserRegistration::class);
    }

    // Helper Methods
    public function isValid()
    {
        // Check if active
        if ($this->status !== 'active') {
            return false;
        }

        // Check expiration
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        // Check max uses
        if ($this->max_uses && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function incrementUsage()
    {
        $this->increment('used_count');
        
        // Auto-deactivate if max uses reached
        if ($this->max_uses && $this->used_count >= $this->max_uses) {
            $this->update(['status' => 'inactive']);
        }
    }

    // Static Methods
    public static function generate($description = null, $maxUses = null, $expiresAt = null)
    {
        return self::create([
            'code' => strtoupper(Str::random(10)),
            'description' => $description,
            'max_uses' => $maxUses,
            'expires_at' => $expiresAt,
            'created_by' => auth()->id()
        ]);
    }
}