<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';
    protected $fillable = [
        'contenu',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
