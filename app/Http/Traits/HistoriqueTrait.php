<?php
namespace App\Http\Traits;
use App\Models\Historique;
use Illuminate\Support\Facades\Auth;

trait HistoriqueTrait
{
    public function creerHistorique($nomAction,$user_id)
    {
        $historique = new Historique([
            'navigateur' => request()->header('User-Agent'),
            'ip' => request()->ip(),
            'action' => $nomAction,
            'date' => date("Y-m-d H:i:s"),
            'user_id' => $user_id
        ]);

        $historique->user()->associate($user_id);
        $historique->save();
        return $historique;
    }
}
