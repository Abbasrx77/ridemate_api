<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\NoteController;
use App\Http\Controllers\API\CommentaireController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::apiResource('inscription',UserController::class);

Route::post('connexion' , [UserController::class, 'login']);




//Routes client protégées //ROUTES PARTAGÉES
Route::group(['middleware' => ['auth:sanctum']],function(){
    
    Route::post('publier_trajet', [UserController::class, 'publierTrajet']);
    Route::get('recuperer_offres', [UserController::class, 'fetchOffres']);
    Route::post('supprimer_offres', [UserController::class, 'supprimer_offre']);
    Route::post('rechercher_trajet', [UserController::class, 'rechercherTrajet']);
    Route::post('reserver', [UserController::class, 'reserver']);
    Route::get('reservations_envoyees', [UserController::class, 'fetchReservationEnvoyees']);
    Route::post('supprimer_reservation', [UserController::class, 'supprimer_reservation']);
    Route::get('reservations_obtenues', [UserController::class, 'fetchReservations']);
    Route::post('traiter_reservation', [UserController::class, 'traiterReservation']);
    Route::post('update', [UserController::class, 'update']);
    Route::get('infos', [UserController::class, 'recupererInfos']);
    Route::get('uids', [UserController::class, 'fetchUid']);
    Route::post('reporter', [UserController::class, 'reporter']);
    Route::post('attribuer_note', [NoteController::class, 'AttribuerNote']);
    Route::post('commenter', [CommentaireController::class, 'store']);

});