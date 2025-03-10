<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\CustomerLogic;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\LoyaltyPointTransaction;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class LoyaltyPointController extends Controller
{
    // v2.8.1 checked
    public function point_transfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'point' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if($request->user()->loyalty_point <= 0 || $request->point < (int)BusinessSetting::where('key','loyalty_point_minimum_point')->first()->value || $request->point > $request->user()->loyalty_point) {
            return response()->json(['errors' => [ ['code' => 'point', 'message' => trans('messages.insufficient_point')]]], 403);
        }

        try
        {
            $wallet_transaction = CustomerLogic::create_wallet_transaction($request->user()->id,$request->point,'loyalty_point',$request->reference);
            CustomerLogic::create_loyalty_point_transaction($request->user()->id, $wallet_transaction->transaction_id, $request->point, 'point_to_wallet');
            Helpers::add_fund_push_notification($request?->user()?->id);
            if(config('mail.status') &&  Helpers::get_mail_status('add_fund_mail_status_user') =='1' &&  Helpers::getNotificationStatusData('store','customer_add_fund_to_wallet','mail_status')) {
                Mail::to($request->user()->email)->send(new \App\Mail\AddFundToWallet($wallet_transaction));
            }

            return response()->json(['message' => translate('messages.point_to_wallet_transfer_successfully')], 200);
        }catch(\Exception $ex){
            info($ex->getMessage());
        }

        return response()->json(['errors' => [ ['code' => 'customer_wallet', 'message' => translate('messages.failed_to_transfer')]]], 203);
    }

    public function transactions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required',
            'offset' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $paginator = LoyaltyPointTransaction::where('user_id', $request->user()->id)->latest()->paginate($request->limit, ['*'], 'page', $request->offset);

        $data = [
            'total_size' => $paginator->total(),
            'limit' => $request->limit,
            'offset' => $request->offset,
            'data' => $paginator->items()
        ];
        return response()->json($data, 200);
    }
}
