<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin;
use App\Models\Store;
use App\Models\AdminWallet;
use App\Models\DeliveryMan;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Exports\CollectCashTransactionExport;
use App\Models\AccountTransaction;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\Validator;

class AccountTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $key = isset($request['search']) ? explode(' ', $request['search']) : [];
        $account_transaction = AccountTransaction::
        when(isset($key), function ($query) use ($key) {
            return $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('ref', 'like', "%{$value}%");
                }
            });
        })->where('type', 'collected' )
            ->whereNull('company_id')   // v2.8.1
            ->latest()->paginate(config('default_pagination'));
        return view('admin-views.account.index', compact('account_transaction'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:store,deliveryman',
            'method' => 'required',
            'store_id' => 'required_if:type,store',
            'deliveryman_id' => 'required_if:type,deliveryman',
            'amount' => 'required|numeric',
        ]);

        if ($request['store_id'] && $request['deliveryman_id']) {
            $validator->getMessageBag()->add('from type', 'Can not select both deliveryman and store');
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }


        if($request['type']=='store' && $request['store_id'])
        {
            $store = Store::findOrFail($request['store_id']);
            $data = $store->vendor;
            $current_balance = $data->wallet?$data->wallet->collected_cash:0;
        }
        else if($request['type']=='deliveryman' && $request['deliveryman_id'])
        {
            $data = DeliveryMan::findOrFail($request['deliveryman_id']);

            $current_balance = $data->wallet?$data->wallet->collected_cash:0;
        }

        if ($current_balance < $request['amount']) {
            $validator->getMessageBag()->add('amount', translate('messages.insufficient_balance'));
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        $account_transaction = new AccountTransaction();
        $account_transaction->from_type = $request['type'];
        $account_transaction->from_id = $data->id;
        $account_transaction->method = $request['method'];
        $account_transaction->ref = $request['ref'];
        $account_transaction->amount = $request['amount'];
        $account_transaction->current_balance = $current_balance;

        try
        {
            DB::beginTransaction();
            $account_transaction->save();
            $data->wallet->decrement('collected_cash', $request['amount']);
            AdminWallet::where('admin_id', Admin::where('role_id', 1)->first()->id)->increment('manual_received', $request['amount']);

            DB::commit();
        }
        catch(\Exception $e)
        {
            DB::rollBack();
            return $e;
        }

        try {

            if( $request['type'] == 'deliveryman' && $request['deliveryman_id'] &&   Helpers::getNotificationStatusData('deliveryman','deliveryman_collect_cash','push_notification_status') && $data->fcm_token){
                $notification_data = [
                    'title' => translate('messages.Cash_Collected'),
                    'description' => translate('messages.Your_hand_in_cash_has_been_collected_by_admin'),
                    'order_id' => '',
                    'image' => '',
                    'type' => 'cash_collect'
                ];
                Helpers::send_push_notif_to_device($data->fcm_token, $notification_data);
                DB::table('user_notifications')->insert([
                    'data' => json_encode($notification_data),
                    'delivery_man_id' => $data->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }


            if($request['type']=='deliveryman' && $request['deliveryman_id'] && config('mail.status') &&  Helpers::get_mail_status('cash_collect_mail_status_dm') == '1'  &&  Helpers::getNotificationStatusData('deliveryman','deliveryman_collect_cash','mail_status')){
                Mail::to($data['email'])->send(new \App\Mail\CollectCashMail($account_transaction,$data['f_name']));
            }
        } catch (\Throwable $th) {

        }
        return response()->json(200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $account_transaction=AccountTransaction::findOrFail($id);
        return view('admin-views.account.view', compact('account_transaction'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        AccountTransaction::where('id', $id)->delete();
        Toastr::success(translate('messages.account_transaction_removed'));
        return back();
    }

    public function export_account_transaction(Request $request){
        $key = isset($request['search']) ? explode(' ', $request['search']) : [];
        $account_transaction = AccountTransaction::
        when(isset($key), function ($query) use ($key) {
            return $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('ref', 'like', "%{$value}%");
                }
            });
        }) ->where('type', 'collected')
            ->latest()->get();

        $data = [
            'account_transactions'=>$account_transaction,
            'search'=>$request->search??null,

        ];

        if ($request->type == 'excel') {
            return Excel::download(new CollectCashTransactionExport($data), 'CollectCashTransactions.xlsx');
        } else if ($request->type == 'csv') {
            return Excel::download(new CollectCashTransactionExport($data), 'CollectCashTransactions.csv');
        }
    }

    public function search_account_transaction(Request $request){
        $key = explode(' ', $request['search']);
        $account_transaction = AccountTransaction::where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('ref', 'like', "%{$value}%");
            }
        })
            ->where('type', 'collected' )
            ->get();

        return response()->json([
            'view'=>view('admin-views.account.partials._table', compact('account_transaction'))->render(),
            'total'=>$account_transaction->count()
        ]);
    }
}
