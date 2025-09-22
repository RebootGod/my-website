<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserActionLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'target_type', 'target_id', 'description', 'ip_address', 'created_at'
    ];
}
