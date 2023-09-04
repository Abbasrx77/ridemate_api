<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Admin;
use App\Models\Conducteur;
use App\Models\Passager;
use App\Models\Notification;
use App\Models\Historique;
use App\Models\Note;
use App\Models\Reservation;
use App\Models\Report;
use App\Models\Commentaire;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

     //ICI JE VAIS: Ajouter le numero de carte d'identité peut-etre
    protected $fillable = [
        'matricule',
        'email',
        'fonction',
        'password',
        'note',
        'zone',
        'fcmToken',
        'uid'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function admin(){
        return $this->belongsTo(Admin::class,'id','user_id');
    }

    public function conducteur(){
        return $this->belongsTo(Conducteur::class,'id','user_id');
    }

    public function passager(){
        return $this->belongsTo(Passager::class,'id','user_id');
    }

    public function notification(){
        return $this->HasMany(Notification::class);
    }

    public function historique(){
        return $this->HasMany(Historique::class);
    }

    public function note(){
        return $this->HasMany(Note::class);
    }

    public function commentaire(){
        return $this->HasMany(Commenaire::class);
    }

    public function offreTrajet(){
        return $this->HasMany(OffreTrajet::class);
    }

    public function reserver(){
        return $this->HasMany(Reservation::class);
    }

    public static function checkMatricule($matricule) {
        $student = DB::connection('eneam_db')->table('etudiants')->where('matricule', $matricule)->first();
    
        return $student !== null;
        }
    public function report(){
        return $this->HasMany(Report::class);
    }
}
