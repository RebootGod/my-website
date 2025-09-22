<?php

// ========================================
// 3. USER REGISTRATION MODEL
// ========================================
// File: app/Models/UserRegistration.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'invite_code_id',
        'ip_address',
        'user_agent'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function inviteCode()
    {
        return $this->belongsTo(InviteCode::class);
    }
}