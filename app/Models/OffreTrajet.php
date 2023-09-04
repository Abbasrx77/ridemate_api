<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class OffreTrajet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'point_depart',
        'point_arrivee',
        'heure_depart',
        'date_depart',
        'place',
        'description',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
}
