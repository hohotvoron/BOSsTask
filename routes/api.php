<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\Referral\ReferralService;
use App\Models\Referral;
use App\Models\ReferralEarning;

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
|
| Текущий мастер приходит в заголовке X-Master-Id и уже разложен
| в атрибуты запроса middleware'ом ResolveCurrentMaster:
|
|     $master = $request->attributes->get('current_master');
|
| Здесь нужно написать три роута — см. README.md.
|
*/

Route::get('/ping', fn () => ['ok' => true]);

Route::post('/referrals/attach', function(Request $request){
    $master = $request->attributes->get('current_master');
    $code = $request->code;
    $servise = new ReferralService;
    if(!$servise->registerReferral($master, $code)){
       return response()->json([
        "message"=>"ошибка",
       ]);
    }
    return response()->json([
        "message"=>"успех!",
    ]);

});
Route::get('/referrals/my', function(){
    $allReferrals = Referral::where('referrer_master_id', 1)
    ->with('referredMaster', 'earned')->get();
    $out = [];
    foreach($allReferrals as $referral){
        $out[] = [
            "имя мастера(реф)" => $referral->referredMaster->name,
            "дата привязки"=>$referral->created_at,
            "cтатус"=>$referral->status,
            "зачислено"=>$referral->earned?->amount,
        ];
    }
    return response()->json($out);
});
Route::get('/referrals/earnings', function(){
    $allEarn = ReferralEarning::where('referrer_master_id', 2)->where('status', 'paid')->sum('amount');
    $pendingCount = ReferralEarning::where('referrer_master_id', 2)->where('status', 'pending')->sum('amount');
    $paidCount = ReferralEarning::where('referrer_master_id', 2)->where('status', 'paid')->sum('amount');
    $referralCount = Referral::where('referrer_master_id', 2)->where('status', 'rewarded')->count();

    return response()->json([
        "всего заработано"=>$allEarn."руб",
        "в обработке"=>$pendingCount."руб",
        "выплачено"=>$paidCount."руб",
        "одобрено рефералов"=>$referralCount,
    ]);

});
// Route::get();

// TODO: POST /api/referrals/attach
// TODO: GET  /api/referrals/my
// TODO: GET  /api/referrals/earnings
