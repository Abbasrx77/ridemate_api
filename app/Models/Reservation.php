<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
    'conducteur_id',
    'passager_id',
    'offre_id',
    'statut',
    'conducteur_notified'
];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
