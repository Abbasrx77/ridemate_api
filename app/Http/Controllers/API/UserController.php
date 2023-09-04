<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\Notification;
use App\Http\Traits\NotificationService;
use App\Notifications\ReservationRecue;
use App\Notifications\ReservationAcceptee;
use App\Http\Traits\HistoriqueTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Admin;
use App\Models\Conducteur;
use App\Models\OffreTrajet;
use App\Models\Passager;
use App\Models\Reservation;
use App\Models\Report;
use Exception;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    use HistoriqueTrait;
    //Injection de dépendences pour l'envoi de notifications
    private $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = User::all();
        return $user;
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        try{
        $request->validate([
            'matricule' => 'required|max:10',
            'email' => 'required|email|unique:users',
            'fonction' => 'required|max:10',
            'password' => 'required|max:50',
            'zone' => 'required|max:50',
            'fcmToken' => 'required',
            'uid' => 'required|'
        ]);

        if(User::checkMatricule($request->matricule)){

        if ($request->fonction == 'admin') {
            $user = $request->except('matricule');
            $user['password'] = Hash::make($request->password);

            $user['matricule'] = '0';

            User::create($user);
            $user = User::where('email', $request->email)->first();

            $admin = Admin::create([
                'user_id' => $user->id
            ]);
            $user->admin()->associate($admin);
            $user->save();

            $user = User::with('admin')->find($user->id);

            return response()->json($user,201);

        } elseif ($request->fonction == 'conducteur') {
            
            $request->validate([
                'vehicule' => 'required',
                'place' => 'required|max:5',
            ]);

            $user = $request->all();
            $user['password'] = Hash::make($request->password);
            User::create($user);

            $user = User::where('email', $request->email)->first();

            $conducteur = Conducteur::create([
                'user_id' => $user->id,
                'vehicule' => $request->vehicule,
                'place' => $request->place
            ]);
            $user->conducteur()->associate($conducteur);
            $user->save();

            $user = User::with('conducteur')->find($user->id);

            return response()->json('Succès',201);
        }
        elseif ($request->fonction == 'passager') {

            $user = $request->all();
            $user['password'] = Hash::make($request->password);
            User::create($user);

            $user = User::where('email', $request->email)->first();

            $passager = Passager::create([
                'user_id' => $user->id
            ]);
            $user->passager()->associate($passager);
            $user->save();

            $user = User::with('passager')->find($user->id);

            return response()->json($user,201);
        }
    }else{
        return response()->json("Etudiant non trouvé",404);
    }
    }catch(Exception $e){
        return response()->json("Veuillez réessayer plus tard".$e,500);
    }

    }

    /**
     * Log the user in.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request){

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {

            /** @var User $user */
            
            $user = Auth::user();
            $fonction = $user->fonction;

            //TEST DE L'ENVOI DE NOTIFICATION
            //$this->notificationService->sendAcceptedReservationNotification($user);
            /////////////////////////////////
            //CREATION DE L'HISTORIQUE
            //$hist = $this->creerHistorique("Connexion", $user->id);
            //////////////////////////////////
            $token = $user->createToken('authToken')->plainTextToken;
            
            $data = [$fonction,$token];
            return response()->json($data, 200);
        }else{
            $data = ['Identifiants incorrects'];
            return response()->json($data,401);
        }
    
    }


    /**
     * Allow driver to post a ride offer.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function publierTrajet(Request $request){
        $request->validate([
            'heure_depart' => 'required',
            'date_depart' => 'required',
            'position' => 'required',
            'place' => 'required',
            'description' => 'required',
            'eneam' => 'required'
        ]);

        $user_id = Auth::user()->id;
        $trajet = $request->all();
        $trajet['user_id'] = $user_id;
        $trajet['place'] = $request->place;


        $dateDepart = Carbon::createFromFormat('Y-m-d', $request->date_depart);
        $heureDepart = Carbon::createFromFormat('H:i', $request->heure_depart);
        $heureDepartComplete = $dateDepart->copy()->setTimeFrom($heureDepart);
        
        $trajet['date_depart'] = $dateDepart;
        
        $trajet['heure_depart'] = $heureDepartComplete;
        
        if ($request->eneam == 'aller_eneam') {
            $trajet['point_depart'] = $request->position;
            $trajet['point_arrivee'] = 'eneam';
        } elseif($request->eneam == 'quitter_eneam') {
            $trajet['point_depart'] = 'eneam';
            $trajet['point_arrivee'] = $request->position;
        }

        $trajet = OffreTrajet::create($trajet);

        return response()->json(200);
    }

    /**
     * Allow driver to get his offers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function fetchOffres(){
        $user_id = Auth::user()->id;
        $matricule = Auth::user()->matricule;

        $user = DB::connection('eneam_db')->table('etudiants')->where('matricule', $matricule)->first();

        $conducteur = Conducteur::where('user_id', $user_id)->first();
        $offres = OffreTrajet::where('user_id',$user_id)->get();
        $response = [];
        foreach ($offres as $offre) {
            $offre['date'] = $offre['date_depart'];
            $offre['heure'] = Carbon::parse($offre['heure_depart'])->format('H:i');
            $offre['lieuDepart'] = strtoupper($offre['point_depart']);
            $offre['lieuArrivee'] = strtoupper($offre['point_arrivee']);
            $offre['description'] = $offre['description'];
            $offre['nomPrenom'] = "$user->nom $user->prenom";
            $offre['typeVehicule'] = ucfirst($conducteur['vehicule']);
            $offre['nombrePlaces'] = $offre['place'];

            array_push($response,$offre);
        }


        return response()->json($response,200);
    }

    public function supprimer_offre(Request $request) {
        $request->validate([
            'date_depart' => 'required',
            'point_depart' => 'required',
            'point_arrivee' => 'required',
            'place' => 'required',
            'description' => 'required',
        ]);
    
        $offre = OffreTrajet::where('date_depart', $request->date_depart)
            ->where('point_depart', $request->point_depart)
            ->where('point_arrivee', $request->point_arrivee)
            ->where('place', $request->place)
            ->where('description', $request->description)
            ->first();
    
        if ($offre && strcasecmp($offre->description, $request->description) === 0) {
            $offre->delete();
            return response()->json($offre, 200);
        } else {
            return response()->json(['message' => 'Offre non trouvée'], 404);
        }
    }
    

    public function rechercherTrajet(Request $request){

        function remove_accents($str) {
            $str = normalizer_normalize($str, \Normalizer::FORM_D);
            $str = preg_replace('/[\x{0300}-\x{036f}]/u', '', $str);
            return $str;
        }
        try{
            $request->validate([
                'heure_depart' => 'required',
                'date_depart' => 'required',
                'zone' => 'required',
                'eneam' => 'required',
            ]);
    
            $date_depart = $request->date_depart;
            $heure_depart = $request->heure_depart;
            $zone = $request->zone;
            $zonesRechercher = preg_split('/[^a-zA-Z0-9]/', $request->zone,0, PREG_SPLIT_NO_EMPTY);
    
            
            if ($request->eneam == "aller_eneam") {
    
            $passager_id = Auth::user()->id;
            $passager = Passager::where('user_id', $passager_id)->first();
            $passager_id = $passager->id;
    
            $reservation_effectuee = Reservation::where('passager_id', $passager_id)->pluck('offre_id');
            $offres_reservees = OffreTrajet::whereIn('id', $reservation_effectuee)->get();
    
            
    
            $offres = OffreTrajet::where('date_depart', $date_depart)->whereNotIn('id', $reservation_effectuee);
            
            $heureDepart = new \DateTime($heure_depart);
            $debut = $heureDepart->sub(new \DateInterval('PT1H'))->format('H:i');
            $fin = $heureDepart->add(new \DateInterval('PT2H'))->format('H:i');
            $offres = $offres->whereBetween(DB::raw('TIME(heure_depart)'), [$debut, $fin])->get();
    
            $filteredData = $offres->filter(function($offre) use ($zonesRechercher) {
            
                $offreZones = preg_split('/[^a-zA-Z0-9]/', $offre->point_depart,0, PREG_SPLIT_NO_EMPTY);
                
                $zoneRecherche = sizeof($zonesRechercher) > 1 ? end($zonesRechercher) : $zonesRechercher[0];
                $zoneRecherche = remove_accents($zoneRecherche);
                
                foreach ($offreZones as $offreZone) {
                    $offreZone = remove_accents($offreZone);
            
                    if (mb_stripos($offreZone, $zoneRecherche) !== false) {
                        return true;
                    }
                }
                return false;
            });
            
            $filteredData = $filteredData->values()->toArray();

            $zoneRecherche = $request->zone;
            usort($filteredData, function($a, $b) use ($zoneRecherche) {
                // Calculer la similarité entre la zone de recherche et le point de départ de chaque offre
                $similarityA = similar_text($zoneRecherche, $a['point_depart']);
                $similarityB = similar_text($zoneRecherche, $b['point_depart']);
            
                // Si la similarité avec A est plus grande que la similarité avec B, A doit venir en premier
                if ($similarityA > $similarityB) {
                    return -1;
                }
                // Si la similarité avec B est plus grande que la similarité avec A, B doit venir en premier
                else if ($similarityA < $similarityB) {
                    return 1;
                }
                // Si la similarité est la même, l'ordre ne change pas
                else {
                    return 0;
                }
            });
            $response = [];
            foreach ($filteredData as $trajet) {
                $post_user = $trajet['user_id'];
                $user = User::where('id', $post_user)->first();
                $matricule = $user->matricule;
                $etudiant = DB::connection('eneam_db')->table('etudiants')->where('matricule', $matricule)->first();
                $conducteur = Conducteur::where('user_id', $post_user)->first();
    
                $trajet['date'] = $trajet['date_depart'];
                $trajet['heure'] = Carbon::parse($trajet['heure_depart'])->format('H:i');
                $trajet['lieuDepart'] = strtoupper($trajet['point_depart']);
                $trajet['lieuArrivee'] = strtoupper($trajet['point_arrivee']);
                $trajet['description'] = $trajet['description'];
                $trajet['nomPrenom'] = "$etudiant->nom $etudiant->prenom";
                $trajet['typeVehicule'] = ucfirst($conducteur['vehicule']);
                $trajet['nombrePlaces'] = $trajet['place'];
                
                array_push($response,$trajet);
            }
            return response()->json($response,200);
    
            }else if($request->eneam == "quitter_eneam") {
            
            $passager_id = Auth::user()->id;
            $passager = Passager::where('user_id', $passager_id)->first();
            $passager_id = $passager->id;
    
            $reservation_effectuee = Reservation::where('passager_id', $passager_id)->pluck('offre_id');
            $offres_reservees = OffreTrajet::whereIn('id', $reservation_effectuee)->get();
    
            $offres = OffreTrajet::where('date_depart', $date_depart)->whereNotIn('id', $reservation_effectuee);
            
            $heureDepart = new \DateTime($heure_depart);
            $debut = $heureDepart->sub(new \DateInterval('PT1H'))->format('H:i');
            $fin = $heureDepart->add(new \DateInterval('PT2H'))->format('H:i');
            $offres = $offres->whereBetween(DB::raw('TIME(heure_depart)'), [$debut, $fin])->get();
    
            $filteredData = $offres->filter(function($offre) use ($zonesRechercher) {
            
                $offreZones = preg_split('/[^a-zA-Z0-9]/', $offre->point_arrivee,0, PREG_SPLIT_NO_EMPTY);
                
                $zoneRecherche = sizeof($zonesRechercher) > 1 ? end($zonesRechercher) : $zonesRechercher[0];
                $zoneRecherche = remove_accents($zoneRecherche);
                
                foreach ($offreZones as $offreZone) {
                    $offreZone = remove_accents($offreZone);
            
                    
                    if (mb_stripos($offreZone, $zoneRecherche) !== false) {
                        return true;
                    }
                }
                return false;
            });
            
            
            $filteredData = $filteredData->values();
            $filteredData = $filteredData->values()->toArray();

            $zoneRecherche = $request->zone;
            usort($filteredData, function($a, $b) use ($zoneRecherche) {
                // Calculer la similarité entre la zone de recherche et le point de départ de chaque offre
                $similarityA = similar_text($zoneRecherche, $a['point_depart']);
                $similarityB = similar_text($zoneRecherche, $b['point_depart']);
            
                // Si la similarité avec A est plus grande que la similarité avec B, A doit venir en premier
                if ($similarityA > $similarityB) {
                    return -1;
                }
                // Si la similarité avec B est plus grande que la similarité avec A, B doit venir en premier
                else if ($similarityA < $similarityB) {
                    return 1;
                }
                // Si la similarité est la même, l'ordre ne change pas
                else {
                    return 0;
                }
            });
            $response = [];
            foreach ($filteredData as $trajet) {
                $post_user = $trajet['user_id'];
                $user = User::where('id', $post_user)->first();
                $matricule = $user->matricule;
                $etudiant = DB::connection('eneam_db')->table('etudiants')->where('matricule', $matricule)->first();
                $conducteur = Conducteur::where('user_id', $post_user)->first();
    
                $trajet['date'] = $trajet['date_depart'];
                $trajet['heure'] = Carbon::parse($trajet['heure_depart'])->format('H:i');
                $trajet['lieuDepart'] = strtoupper($trajet['point_depart']);
                $trajet['lieuArrivee'] = strtoupper($trajet['point_arrivee']);
                $trajet['description'] = $trajet['description'];
                $trajet['nomPrenom'] = "$etudiant->nom $etudiant->prenom";
                $trajet['typeVehicule'] = ucfirst($conducteur['vehicule']);
                $trajet['nombrePlaces'] = $trajet['place'];
                
                array_push($response,$trajet);
            }
            return response()->json($response,200);
    
            }
        }catch(Exception $e){
            return response()->json("Erreur interne du serveur",500);
        }
        
    }

    public function reserver(Request $request){
        $request->validate([
            'date_depart' => 'required',
            'heure_depart' => 'required',
            'point_depart' => 'required',
            'point_arrivee' => 'required',
            'place' => 'required',
            'description' => 'required',
            'identite' => 'required',
        ]);

        $date_depart = $request->date_depart;
        $heure_depart = $request->heure_depart;
        $point_depart = $request->point_depart;
        $point_arrivee = $request->point_arrivee;
        $place = $request->place;
        $description = $request->description;
        $identite = $request->identite;

        $nom_prenom = explode(" ", $identite);
        $nom_conducteur = $nom_prenom[0];
        $prenom_conducteur = $nom_prenom[1];

        $passager_id = Auth::user()->id;
        $passager = Passager::where('user_id', $passager_id)->first();
        $passager_id = $passager->id;
        $etudiant = DB::connection('eneam_db')->table('etudiants')->where('nom', $nom_conducteur)
        ->where('prenom',$prenom_conducteur)->first();
        $matricule = $etudiant->matricule;
        $user = User::where('matricule',$matricule)->first();
        $conducteur = Conducteur::where('user_id',$user->id)->first();
        $conducteur_id = $conducteur->id;
        $conducteur_user = User::where('id',$conducteur->user_id)->first();
        $fcm_notif_cond_reservation = $conducteur_user->fcmToken;
        

        $offre = OffreTrajet::where('date_depart',$date_depart)->where('heure_depart',$heure_depart)
        ->where('point_depart',$point_depart)->where('point_arrivee',$point_arrivee)->where('place',$place)
        ->where('description',$description)->where('user_id',$conducteur_id)->first();
        $offre_id = $offre->id;

        $reservation = new Reservation();
        $reservation->conducteur_id = $conducteur_id;
        $reservation->passager_id = $passager_id;
        $reservation->offre_id = $offre_id;

        $reservation->save();

        return response()->json($fcm_notif_cond_reservation,201);
    }

    public function fetchReservationEnvoyees(){
        $user_id = Auth::user()->id;
        $matricule = Auth::user()->matricule;

        $user = DB::connection('eneam_db')->table('etudiants')->where('matricule', $matricule)->first();

        $passager = Passager::where('user_id', $user_id)->first();
        $passager_id = $passager->id;
        $reservations = Reservation::where('passager_id',$passager_id)->where('statut','en_attente')->get();
        $response = [];
        foreach ($reservations as $reservation) {
            $reservation_offre_id = $reservation->offre_id;

            $conducteur = Conducteur::where('id', $reservation->conducteur_id)->first();
            $conducteur_user_id = $conducteur->user_id;
            $conducteur_user = User::where('id', $conducteur_user_id)->first();

            $offre = OffreTrajet::where('id', $reservation_offre_id)->first();
            if($offre != null){
            $etudiant_conducteur = DB::connection('eneam_db')->table('etudiants')->where('matricule', $conducteur_user->matricule)->first();

            $offre['date'] = $offre['date_depart'];
            $offre['heure'] = Carbon::parse($offre['heure_depart'])->format('H:i');
            $offre['lieuDepart'] = strtoupper($offre['point_depart']);
            $offre['lieuArrivee'] = strtoupper($offre['point_arrivee']);
            $offre['description'] = $offre['description'];
            $offre['nomPrenom'] = "$etudiant_conducteur->nom $etudiant_conducteur->prenom";
            $offre['typeVehicule'] = ucfirst($conducteur['vehicule']);
            $offre['nombrePlaces'] = $conducteur['place'];

            array_push($response,$offre);
            }
            
        }


        return response()->json($response,200);
    }

    public function supprimer_reservation(Request $request){
        $request->validate([
            'date_depart' => 'required',
            'point_depart' => 'required',
            'point_arrivee' => 'required',
            'place' => 'required',
            'description' => 'required',
        ]);
        $user = Auth::user();
        $passager = Passager::where('user_id', $user->id)->first();
        $offre = OffreTrajet::where('date_depart',$request->date_depart)->where('point_depart',$request->point_depart)->where('point_arrivee',$request->point_arrivee)->where('place',$request->place)->where('description',$request->description)->first();
        $reservation = Reservation::where('offre_id',$offre->id)->where('passager_id',$passager->id)->first();
        $reservation->delete();

        return response()->json($reservation,200);
    }

    public function fetchReservations(){
        $user = Auth::user();
        $conducteur = Conducteur::where('user_id',$user->id)->first();
        $reservations = Reservation::where('conducteur_id',$conducteur->id)->where('statut','en_attente')->get();

        $response = [];
        foreach($reservations as $reservation){
            $offre = OffreTrajet::where('id',$reservation->offre_id)->first();
            if($offre != null){
            $passager = Passager::where('id',$reservation->passager_id)->first();
            $passager_user = User::where('id',$passager->user_id)->first();
            $passager_user = DB::connection('eneam_db')->table('etudiants')->where('matricule', $passager_user->matricule)->first();

            $offre['date'] = $offre['date_depart'];
            $offre['heure'] = Carbon::parse($offre['heure_depart'])->format('H:i');
            $offre['lieuDepart'] = strtoupper($offre['point_depart']);
            $offre['lieuArrivee'] = strtoupper($offre['point_arrivee']);
            $offre['nomPrenom'] = "$passager_user->nom $passager_user->prenom";
            $offre['typeVehicule'] = ucfirst($conducteur['vehicule']);
            $offre['nombrePlaces'] = $offre['place'];

            array_push($response,$offre);
            }
        }
        return response()->json($response,200);
    }

    public function traiterReservation(Request $request){
        $request->validate([
            'traitement' => 'required',
            'date_depart' => 'required',
            'point_depart' => 'required',
            'point_arrivee' => 'required',
            'nomPrenom' => 'required',
            'place' => 'required',
        ]);

        $user = Auth::user();
        $conducteur = Conducteur::where('user_id', $user->id)->first();
        if($request->traitement == "accepter"){
            
            $nom_prenom = explode(" ", $request->nomPrenom);
            $nom_passager = $nom_prenom[0];
            $prenom_passager = $nom_prenom[1];

            $etudiant_passager = DB::connection('eneam_db')->table('etudiants')->where('nom', $nom_passager)
            ->where('prenom',$prenom_passager)->first();

            $etudiant_user = User::where('matricule',$etudiant_passager->matricule)->first();
            $fcmToken = $etudiant_user->fcmToken;
            $uid = $etudiant_user->uid;

            $passager = Passager::where('user_id',$etudiant_user->id)->first();

            $reservation = Reservation::where('conducteur_id',$conducteur->id)->where('passager_id',$passager->id)
            ->where('statut','en_attente')->first();

            $reservation->statut = 'confirme';
            $reservation->save();

            $data = [$fcmToken,$uid];

            return response()->json($data,200);

        }else if($request->traitement == "refuser"){

            $nom_prenom = explode(" ", $request->nomPrenom);
            $nom_passager = $nom_prenom[0];
            $prenom_passager = $nom_prenom[1];

            $etudiant_passager = DB::connection('eneam_db')->table('etudiants')->where('nom', $nom_passager)
            ->where('prenom',$prenom_passager)->first();

            $etudiant_user = User::where('matricule',$etudiant_passager->matricule)->first();

            $passager = Passager::where('user_id',$etudiant_user->id)->first();

            $reservation = Reservation::where('conducteur_id',$conducteur->id)->where('passager_id',$passager->id)
            ->where('statut','en_attente')->first();

            $reservation->delete();

            return response()->json(200);
        }
    }

    public function fetchUid(){
        $user = Auth::user();
        $passager = Passager::where('user_id',$user->id)->first();

        $reservations = Reservation::where('passager_id',$passager->id)->where('statut','confirme')->get();
        $uids = [];

        foreach($reservations as $reservation){
            $conducteur_id = $reservation->conducteur_id;
            $conducteur = Conducteur::where('id',$conducteur_id)->first();
            $user = User::where('id',$conducteur->user_id)->first();
            $uid = $user->uid;
            array_push($uids,$uid);
        }
        return response()->json($uids);
    }
    
    public function reporter(Request $request){
        try{
        $user_id = Auth::user()->id;
        $report = $request->all();
        if($request->type_erreur){
            $report['type_erreur'] = $request->type_erreur;
        }else{
            $report['type_erreur'] = 'indefini';
        }
        if($request->informations){
            $report['informations'] = $request->informations;
        }else{
            $report['informations'] = 'indefini';
        }
        $report['user_id'] = $user_id;
        $report = Report::create($report);

        return response()->json(200);
        }catch(Exception $e){
            return response()->json('Erreur interne du serveur',500);
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::findOne($id);
        return $user;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $user_id = Auth::user()->id;
        $user = User::where('id',$user_id)->first();
        $data = $request->all();
        $conducteur = Conducteur::where('id', $user->id)->first();
        if($conducteur){
            $user->update($data);
            $conducteur->update($data);
        }
        $user->update($data);
        $user->save();
        return response()->json("Mise à jour effectuée avec succès",200);
    }
    public function recupererInfos(){
        $user = Auth::user();
        $infos = DB::connection('eneam_db')->table('etudiants')->where('matricule', $user->matricule)->first();

        if($user->fonction ==  "conducteur"){
            $conducteur = Conducteur::where('user_id',$user->id)->first();
            $informations = [];
            $informations['nom'] = $infos->nom;
            $informations['prenom'] = $infos->prenom;
            $informations['email'] = $user->email;
            $informations['zone'] = $user->zone;
            $informations['place'] = $conducteur->place;
            $informations['vehicule'] = $conducteur->vehicule;
            return response()->json($informations,200);
        }else if($user->fonction ==  "passager"){
            $passager = Passager::where('user_id',$user->id)->first();
            $informations = [];
            $informations['nom'] = $infos->nom;
            $informations['prenom'] = $infos->prenom;
            $informations['email'] = $user->email;
            $informations['zone'] = $user->zone;
            return response()->json($informations,200);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        User::remove($id);
    }
}
