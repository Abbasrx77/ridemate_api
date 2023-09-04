<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Conducteur extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'vehicule',
        'place'
    ];

    public function user(){
        return $this->hasOne(User::class,'id','user_id');
    }
}
