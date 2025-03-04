<?php

namespace App\Http\Controllers;

use App\Mail\StoreRegistration;
use App\Mail\VendorSelfRegistration;
use App\Models\Zone;
use App\Models\Admin;
use App\Models\Store;
use App\Models\Module;
use App\Models\Vendor;
use App\Models\Translation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use App\CentralLogics\StoreLogic;
use Illuminate\Support\Facades\DB;
use App\Models\SubscriptionPackage;
use Gregwar\Captcha\CaptchaBuilder;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Modules\Rental\Emails\ProviderRegistration;
use Modules\Rental\Emails\ProviderSelfRegistration;

class VendorController extends Controller
{
    public function showAgreement($key)
    {
        if ($key == 'store')
        {
            $agreement = \App\Models\BusinessSetting::where('key', 'store_agreement')->first();
        } elseif ($key == 'dm')
        {
            $agreement = \App\Models\BusinessSetting::where('key', 'dm_agreement')->first();
        } elseif ($key == 'courier')
        {
            $agreement = \App\Models\BusinessSetting::where('key', 'courier_company_agereement')->first();
        }
//        return view('vendor.agreement', ['agreement' => $agreement ?? '']);
        if (str()->contains(url()->current(), '/api/'))
        {
            if (empty($agreement))
            {
                return response()->json('Agreement data not found', 404);
            } else {
//                $agreement->value = strip_tags($agreement->value);
                return response()->json($agreement->value ?? '', 200);
            }
        } else {
            return view('vendor.agreement', ['agreement' => $agreement ?? '']);
        }
    }
    public function create()
    {
        $status = BusinessSetting::where('key', 'toggle_store_registration')->first();
        if(!isset($status) || $status->value == '0')
        {
            Toastr::error(translate('messages.not_found'));
            return back();
        }
        $admin_commission= BusinessSetting::where('key','admin_commission')->first()?->value;
        $business_name= BusinessSetting::where('key','business_name')->first()?->value;
        $packages= SubscriptionPackage::where('status',1)->latest()->get();
        $custome_recaptcha = new CaptchaBuilder;
        $custome_recaptcha->build();
        Session::put('six_captcha', $custome_recaptcha->getPhrase());

        return view('vendor-views.auth.general-info', compact('custome_recaptcha','admin_commission','business_name','packages' ));
    }

    public function store(Request $request)
    {
        $status = BusinessSetting::where('key', 'toggle_store_registration')->first();
        if(!isset($status) || $status->value == '0')
        {
            Toastr::error(translate('messages.not_found'));
            return back();
        }

        $recaptcha = Helpers::get_business_settings('recaptcha');
//        if (isset($recaptcha) && $recaptcha['status'] == 1) {
//            $request->validate([
//                'g-recaptcha-response' => [
//                    function ($attribute, $value, $fail) {
//                        $secret_key = Helpers::get_business_settings('recaptcha')['secret_key'];
//                        $gResponse = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
//                            'secret' => $secret_key,
//                            'response' => $value,
//                            'remoteip' => \request()->ip(),
//                        ]);
//
//                        if (!$gResponse->successful()) {
//                            $fail(translate('ReCaptcha Failed'));
//                        }
//                    },
//                ],
//            ]);
//        } else if(strtolower(session('six_captcha')) != strtolower($request->custome_recaptcha))
//        {
//            Toastr::error(translate('messages.ReCAPTCHA Failed'));
//            return back();
//        }

        $validator = Validator::make($request->all(), [
            'f_name' => 'required',
            'name' => 'required',
            'address' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'email' => 'required|unique:vendors',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|unique:vendors',
            'minimum_delivery_time' => 'required',
            'maximum_delivery_time' => 'required',
            'password' => ['required', Password::min(8)/*->mixedCase()->letters()->numbers()->symbols()*/],
            'zone_id' => 'required',
            'module_id' => 'required',
            'logo' => 'required',
            'tax' => 'required',
            'delivery_time_type'=>'required',
            'tax_id'=>'required|unique:stores', // v2.8.1
            'register_no'=>'required',  // v2.8.1
            'tax_document'=>'required|file|max:5120|mimes:jpg,png,jpeg,gif,bmp,tif,tiff', // v2.8.1
            'registration_document'=>'required|file|max:5120|mimes:jpg,png,jpeg,gif,bmp,tif,tiff', // v2.8.1
        ],[
            'password.min_length' => translate('The password must be at least :min characters long'),
//            'password.mixed' => translate('The password must contain both uppercase and lowercase letters'),
//            'password.letters' => translate('The password must contain letters'),
//            'password.numbers' => translate('The password must contain numbers'),
//            'password.symbols' => translate('The password must contain symbols'),
//            'password.uncompromised' => translate('The password is compromised. Please choose a different one'),
//            'password.custom' => translate('The password cannot contain white spaces.'),
        ]);
        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }
        if($request->zone_id)
        {
            $zone = Zone::query()
            ->whereContains('coordinates', new Point($request->latitude, $request->longitude, POINT_SRID))
            ->where('id',$request->zone_id)
            ->first();
            if(!$zone){
                $validator->getMessageBag()->add('latitude', translate('messages.coordinates_out_of_zone'));
                return back()->withErrors($validator)
                        ->withInput();
            }
        }

        $module = Module::find($request['module_id']);
        if ($module?->module_type == 'rental' && addon_published_status('Rental') && empty($request['pickup_zone_id'])){
            $validator->getMessageBag()->add('pickup_zone_id', translate('messages.You_must_select_a_pickup_zone'));
            return back()->withErrors($validator)
                ->withInput();
        }

        if ($request->business_plan == 'subscription-base' && $request->package_id == null ) {
            $validator->getMessageBag()->add('package_id', translate('messages.You_must_select_a_package'));
            return back()->withErrors($validator)
                    ->withInput();
        }

        $vendor = new Vendor();
        $vendor->f_name = $request->f_name;
        $vendor->l_name = $request->l_name;
        $vendor->email = $request->email;
        $vendor->phone = $request->phone;
        $vendor->password = bcrypt($request->password);
        $vendor->status = null;
        $vendor->save();

        $store = new Store;
        $store->name =  $request->name[array_search('default', $request->lang)];
        $store->phone = $request->phone;
        $store->email = $request->email;
        $store->logo = Helpers::upload('store/', 'png', $request->file('logo'));
        $store->cover_photo = Helpers::upload('store/cover/', 'png', $request->file('cover_photo'));
        $store->address = $request->address[array_search('default', $request->lang)];
        $store->latitude = $request->latitude;
        $store->longitude = $request->longitude;
        $store->vendor_id = $vendor->id;
        $store->zone_id = $request->zone_id;
        $store->module_id = $request->module_id;
        $store->tax = $request->tax;
        $store->delivery_time = $request->minimum_delivery_time .'-'. $request->maximum_delivery_time.' '.$request->delivery_time_type;
        $store->status = 0;
        $store->store_business_model = 'none';

        $store->tax_id =  $request->tax_id; // v2.8.1
        $store->register_no = $request->register_no;    // v2.8.1
        $tax_document_extension = $request->file('tax_document')->extension();  // v2.8.1
        $store->tax_document = Helpers::upload('store/', $tax_document_extension, $request->file('tax_document'));  // v2.8.1
        $registration_document_extension = $request->file('registration_document')->extension();    // v2.8.1
        $store->registration_document = Helpers::upload('store/', $registration_document_extension, $request->file('registration_document'));   // v2.8.1
        if ($request->hasFile('agreement_document')) // mainul  // v2.8.1
        {   // v2.8.1
            $agreement_document_extension = $request->file('agreement_document')->extension();  // v2.8.1
            $store->agreement_document = Helpers::upload('store/', $agreement_document_extension, $request->file('agreement_document'));    // v2.8.1
        }   // v2.8.1

        $store->save();


        Helpers::add_or_update_translations(request: $request, key_data: 'name', name_field: 'name', model_name: 'Store', data_id: $store->id, data_value: $store->name);
        Helpers::add_or_update_translations(request: $request, key_data: 'address', name_field: 'address', model_name: 'Store', data_id: $store->id, data_value: $store->address);


        try{
            $admin= Admin::where('role_id', 1)->first();
//            old v2.12 code starts
//            if(config('mail.status') && Helpers::get_mail_status('registration_mail_status_store') == '1' &&  Helpers::getNotificationStatusData('store','store_registration','mail_status') ){
//                Mail::to($request['email'])->send(new \App\Mail\VendorSelfRegistration('pending', $vendor->f_name.' '.$vendor->l_name, 'store'));
//            }
//            if(config('mail.status') && Helpers::get_mail_status('store_registration_mail_status_admin') == '1' &&  Helpers::getNotificationStatusData('admin','store_self_registration','mail_status') ){
//                Mail::to($admin['email'])->send(new \App\Mail\StoreRegistration('pending', $vendor->f_name.' '.$vendor->l_name));
//            }
//            old v2.12 code ends
            if($module?->module_type != 'rental' && config('mail.status') && Helpers::get_mail_status('registration_mail_status_store') == '1' &&  Helpers::getNotificationStatusData('store','store_registration','mail_status') ){
                Mail::to($request['email'])->send(new VendorSelfRegistration('pending', $vendor->f_name.' '.$vendor->l_name, 'store'));
            }
            elseif($module?->module_type == 'rental' && addon_published_status('Rental')&& config('mail.status') && Helpers::get_mail_status('rental_registration_mail_status_provider') == '1' &&  Helpers::getRentalNotificationStatusData('provider','provider_registration','mail_status') ){
                Mail::to($request['email'])->send(new ProviderSelfRegistration('pending', $vendor->f_name.' '.$vendor->l_name));
            }

            if($module?->module_type != 'rental' && config('mail.status') && Helpers::get_mail_status('store_registration_mail_status_admin') == '1' &&  Helpers::getNotificationStatusData('admin','store_self_registration','mail_status') ){
                Mail::to($admin['email'])->send(new StoreRegistration('pending', $vendor->f_name.' '.$vendor->l_name));
            } elseif($module?->module_type == 'rental' && addon_published_status('Rental')&& config('mail.status') && Helpers::get_mail_status('rental_provider_registration_mail_status_admin') == '1' &&  Helpers::getRentalNotificationStatusData('admin','provider_self_registration','mail_status') ){
                Mail::to($admin['email'])->send(new ProviderRegistration('pending', $vendor->f_name.' '.$vendor->l_name));
            }
        }catch(\Exception $ex){
            info($ex->getMessage());
        }


        if(config('module.'.$store->module->module_type)['always_open'])
        {
            StoreLogic::insert_schedule($store->id);
        }

        if (Helpers::subscription_check()) {
                    if ($request->business_plan == 'subscription-base' && $request->package_id != null ) {
                        $key=['subscription_free_trial_days','subscription_free_trial_type','subscription_free_trial_status'];
                        $free_trial_settings=BusinessSetting::whereIn('key', $key)->pluck('value','key');
                        $store->package_id = $request->package_id;
                        $store->save();

                        return view('vendor-views.auth.register-subscription-payment',[
                        'package_id'=> $request->package_id,
                        'store_id' => $store->id,
                        'free_trial_settings'=>$free_trial_settings,
                        'payment_methods' => Helpers::getDefaultPaymentMethods(),

                        ]);
                    }
                    elseif($request->business_plan == 'commission-base' ){
                        $store->store_business_model = 'commission';
                        $store->save();
                        return view('vendor-views.auth.register-complete',[
                            'type'=>'commission'
                        ]);
                    }
                    else{
                        $admin_commission= BusinessSetting::where('key','admin_commission')->first();
                        $business_name= BusinessSetting::where('key','business_name')->first();
                        $packages= SubscriptionPackage::where('status',1)->get();
                        Toastr::error(translate('messages.please_follow_the_steps_properly.'));
                        return view('vendor-views.auth.register-step-2',[
                            'admin_commission'=> $admin_commission?->value,
                            'business_name'=> $business_name?->value,
                            'packages'=> $packages,
                            'store_id' =>$store->id,
                            'type'=>$request->type
                            ]);
                    }
            } else{
                $store->store_business_model = 'commission';
                $store->save();
                Toastr::success(translate('messages.your_store_registration_is_successful'));
                return view('vendor-views.auth.register-complete',[
                    'type'=>'commission'
                ]);
                }


        Toastr::success(translate('messages.application_placed_successfully'));
        return back();
    }

    public function get_all_modules(Request $request){
        $module_data = Module::Active()->whereHas('zones', function($query)use ($request){
            $query->where('zone_id', $request->zone_id);
        })->notParcel()
        ->where('modules.module_name', 'like', '%'.$request->q.'%')
        ->limit(8)->get()->map(function($module) {
            return [
                'id' => $module->id,
                'text' => $module->module_name
            ];
        });
        return response()->json($module_data);
    }

    public function get_modules_type(Request $request): JsonResponse
    {
        $module = Module::find($request->id);
        $packages=null;


        if ($module) {
            $packages= SubscriptionPackage::where('status',1)->where('module_type',$module?->module_type == 'rental' && addon_published_status('Rental') ? 'rental' : 'all')->latest()->get();

            $module = $module->module_type;
            return response()->json([
                'module_type' => $module,
                'view' => view('vendor-views.auth._package_data', compact('packages','module'))->render(),
            ]);
            // return response()->json(['module_type' => $module->module_type, '' => $packages ?? null]);
        }

        return response()->json(['module_type' => '']);
    }

    public function business_plan(Request $request){
        $store=Store::find($request->store_id);

        if ($request->business_plan == 'subscription-base' && $request->package_id != null ) {
            $key=['subscription_free_trial_days','subscription_free_trial_type','subscription_free_trial_status'];
            $free_trial_settings=BusinessSetting::whereIn('key', $key)->pluck('value','key');

            return view('vendor-views.auth.register-subscription-payment',[
            'package_id'=> $request->package_id,
            'store_id' => $request->store_id,
            'free_trial_settings'=>$free_trial_settings,
            'payment_methods' => Helpers::getDefaultPaymentMethods(),

            ]);
        }
        elseif($request->business_plan == 'commission-base' ){
            $store->store_business_model = 'commission';
            $store->save();
            return view('vendor-views.auth.register-complete',[
                'type'=>'commission'
            ]);
        }
        else{
            $admin_commission= BusinessSetting::where('key','admin_commission')->first();
            $business_name= BusinessSetting::where('key','business_name')->first();
            $packages= SubscriptionPackage::where('status',1)->get();
            Toastr::error(translate('messages.please_follow_the_steps_properly.'));
            return view('vendor-views.auth.register-step-2',[
                'admin_commission'=> $admin_commission?->value,
                'business_name'=> $business_name?->value,
                'packages'=> $packages,
                'store_id' => $request->store_id,
                'type'=>$request->type
                ]);
        }

    }

    public function payment(Request $request){
        $request->validate([
            'package_id' => 'required',
            'store_id' => 'required',
            'payment' => 'required'
        ]);

        $store= Store::Where('id',$request->store_id)->first(['id','vendor_id']);
        $package = SubscriptionPackage::withoutGlobalScope('translate')->find($request->package_id);

        if(!in_array($request->payment,['free_trial'])){
            $url= route('restaurant.final_step',['store_id' => $store->id?? null]);
            return redirect()->away(Helpers::subscriptionPayment(store_id:$store->id,package_id:$package->id,payment_gateway:$request->payment,payment_platform:'web',url:$url,type: 'new_join'));
        }
        if($request->payment == 'free_trial'){
            $plan_data=   Helpers::subscription_plan_chosen(store_id:$store->id,package_id:$package->id,payment_method:'free_trial',discount:0,reference:'free_trial',type: 'new_join');
        }
        $plan_data != false ?  Toastr::success( translate('Successfully_Subscribed.')) : Toastr::error( translate('Something_went_wrong!.'));
        return to_route('restaurant.final_step');
    }

public function back(Request $request){
    $admin_commission= BusinessSetting::where('key','admin_commission')->first();
    $business_name= BusinessSetting::where('key','business_name')->first();
    $store=Store::where('id',$request->store_id)->with('module')->first();
    $module=$store?->module?->module_type ?? 'all';
//    $packages= SubscriptionPackage::where('status',1)->get();
    $packages= SubscriptionPackage::where('status',1)->where('module_type',  $module == 'rental' ? 'rental' : 'all')->get();
    return view('vendor-views.auth.register-step-2',[
        'admin_commission'=> $admin_commission?->value,
        'business_name'=> $business_name?->value,
        'packages'=> $packages,
        'store_id' => $request->store_id,
        'module' => $module
        ]);
}


public function final_step(Request $request){


    $store_id= null;
    $payment_status= null;
    if($request?->store_id && is_string($request?->store_id)){
        $data = explode('?', $request?->store_id);
        $store_id = $data[0];
        $payment_status = $data[1]  != 'flag=success' ? 'fail': 'success';
    }

    return view('vendor-views.auth.register-complete',['store_id' =>$store_id,'payment_status'=> $payment_status]);
}

    public function download_store_agereement(){
        $store_agreement = \App\Models\BusinessSetting::where('key', 'store_agreement')->first();
        $fileName=$store_agreement->value;
        $path = '/agereement/';
        if (Storage::disk('public')->exists($path . $fileName)) {
            return Response::download(storage_path('app/public/agereement/' . $fileName));
        }
    }

    public function download_courier_company_agereement(){
        $courier_company_agereement = \App\Models\BusinessSetting::where('key', 'courier_company_agereement')->first();
        $fileName=$courier_company_agereement->value;
        $path = '/agereement/';
        if (Storage::disk('public')->exists($path . $fileName)) {
            return Response::download(storage_path('app/public/agereement/' . $fileName));
        }
    }
    public function company_create()
    {
        $status = BusinessSetting::where('key', 'toggle_store_registration')->first();
        if(!isset($status) || $status->value == '0')
        {
            Toastr::error(translate('messages.not_found'));
            return back();
        }
        $custome_recaptcha = new CaptchaBuilder;
        $custome_recaptcha->build();
        Session::put('six_captcha', $custome_recaptcha->getPhrase());

        return view('vendor-views.auth.company-register', compact('custome_recaptcha'));
    }

    public function company_store(Request $request)
    {
        $status = BusinessSetting::where('key', 'toggle_store_registration')->first();
        if(!isset($status) || $status->value == '0')
        {
            Toastr::error(translate('messages.not_found'));
            return back();
        }

        $recaptcha = Helpers::get_business_settings('recaptcha');
        if (isset($recaptcha) && $recaptcha['status'] == 1) {
            $request->validate([
                'g-recaptcha-response' => [
                    function ($attribute, $value, $fail) {
                        $secret_key = Helpers::get_business_settings('recaptcha')['secret_key'];
                        $response = $value;
                        $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $response;
                        $response = Http::get($url);
                        $response = $response->json();
                        if (!isset($response['success']) || !$response['success']) {
                            $fail(translate('messages.ReCAPTCHA Failed'));
                        }
                    },
                ],
            ]);
        } else if(strtolower(session('six_captcha')) != strtolower($request->custome_recaptcha))
        {
            Toastr::error(translate('messages.ReCAPTCHA Failed'));
            return back();
        }

        $validator = Validator::make($request->all(), [
            'f_name' => 'required|max:100',
            'l_name' => 'nullable|max:100',
            'name.0' => 'required',
            'name.*' => 'max:191',
            'address' => 'required|max:1000',
            'latitude' => 'required',
            'longitude' => 'required',
            'email' => 'required|unique:vendors',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|max:20|unique:vendors',
            // 'minimum_delivery_time' => 'required',
            // 'maximum_delivery_time' => 'required',
            // 'delivery_time_type'=>'required',
            'password' => ['required', Password::min(8)->mixedCase()->letters()->numbers()->symbols()->uncompromised()],
            'zone_id' => 'required',
            // 'module_id' => 'required',
            'logo' => 'required',
            // 'tax' => 'required',
            'tax_id'=>'required|unique:stores',
            'register_no'=>'required',
            'tax_document'=>'required|file|max:5120|mimes:jpg,png,jpeg,gif,bmp,tif,tiff',
            'registration_document'=>'required|file|max:5120|mimes:jpg,png,jpeg,gif,bmp,tif,tiff',
//            'agreement_document'=>'file|max:5120|mimes:jpg,png,jpeg,gif,bmp,tif,tiff',
        ], [
            'f_name.required' => translate('messages.first_name_is_required'),
            'name.0.required'=>translate('default_name_is_required'),
        ]);
        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }
        if($request->zone_id)
        {
            $zone = Zone::query()
                ->whereContains('coordinates', new Point($request->latitude, $request->longitude, POINT_SRID))
                ->where('id',$request->zone_id)
                ->first();
            if(!$zone){
                $validator->getMessageBag()->add('latitude', translate('messages.coordinates_out_of_zone'));
                return back()->withErrors($validator)
                    ->withInput();
            }
        }

        $vendor = new Vendor();
        $vendor->f_name = $request->f_name;
        $vendor->l_name = $request->l_name;
        $vendor->email = $request->email;
        $vendor->phone = $request->phone;
        $vendor->password = bcrypt($request->password);
        $vendor->status = null;
        $vendor->save();

        $store = new Store;
        $store->name =  $request->name[array_search('default', $request->lang)];
        $store->phone = $request->phone;
        $store->email = $request->email;
        $store->logo = Helpers::upload('store/', 'png', $request->file('logo'));
        $store->cover_photo = Helpers::upload('store/cover/', 'png', $request->file('cover_photo'));
        $store->address = $request->address[array_search('default', $request->lang)];
        $store->latitude = $request->latitude;
        $store->longitude = $request->longitude;
        $store->vendor_id = $vendor->id;
        $store->zone_id = $request->zone_id;
        // $store->module_id = $request->module_id;
        // $store->tax = $request->tax;
        // $store->delivery_time = $request->minimum_delivery_time .'-'. $request->maximum_delivery_time.' '.$request->delivery_time_type;
        $store->status = 0;
        $store->tax_id =  $request->tax_id;
        $store->register_no = $request->register_no;

        $tax_document_extension = $request->file('tax_document')->extension();
        $store->tax_document = Helpers::upload('store/', $tax_document_extension, $request->file('tax_document'));

        $registration_document_extension = $request->file('registration_document')->extension();
        $store->registration_document = Helpers::upload('store/', $registration_document_extension, $request->file('registration_document'));

//        $agreement_document_extension = $request->file('agreement_document')->extension();
//        $store->agreement_document = Helpers::upload('store/', $agreement_document_extension, $request->file('agreement_document'));

        $store->store_type="company";
        $store->item_section=0;
        $store->reviews_section=0;
        $store->veg=0;
        $store->non_veg=0;
        $store->self_parcel_delivery=1;
        $store->module_id = 5;

        $store->save();

        $default_lang = str_replace('_', '-', app()->getLocale());
        $data = [];
        foreach ($request->lang as $index => $key) {
            if($default_lang == $key && !($request->name[$index])){
                if ($key != 'default') {
                    array_push($data, array(
                        'translationable_type' => 'App\Models\Store',
                        'translationable_id' => $store->id,
                        'locale' => $key,
                        'key' => 'name',
                        'value' => $store->name,
                    ));
                }
            }else{
                if ($request->name[$index] && $key != 'default') {
                    array_push($data, array(
                        'translationable_type' => 'App\Models\Store',
                        'translationable_id' => $store->id,
                        'locale' => $key,
                        'key' => 'name',
                        'value' => $request->name[$index],
                    ));
                }
            }
            if($default_lang == $key && !($request->address[$index])){
                if ($key != 'default') {
                    array_push($data, array(
                        'translationable_type' => 'App\Models\Store',
                        'translationable_id' => $store->id,
                        'locale' => $key,
                        'key' => 'address',
                        'value' => $store->address,
                    ));
                }
            }else{
                if ($request->address[$index] && $key != 'default') {
                    array_push($data, array(
                        'translationable_type' => 'App\Models\Store',
                        'translationable_id' => $store->id,
                        'locale' => $key,
                        'key' => 'address',
                        'value' => $request->address[$index],
                    ));
                }
            }
        }
        Translation::insert($data);
        try{
            $admin= Admin::where('role_id', 1)->first();
            $mail_status = Helpers::get_mail_status('registration_mail_status_store');
            if(config('mail.status') && $mail_status == '1'){
                Mail::to($request['email'])->send(new \App\Mail\VendorSelfRegistration('pending', $vendor->f_name.' '.$vendor->l_name,'company'));
            }
            $mail_status = Helpers::get_mail_status('store_registration_mail_status_admin');
            if(config('mail.status') && $mail_status == '1'){
                Mail::to($admin['email'])->send(new \App\Mail\StoreRegistration('pending', $vendor->f_name.' '.$vendor->l_name));
            }
        }catch(\Exception $ex){
            info($ex->getMessage());
        }


        if(config('module.'.$store->module->module_type)['always_open'])
        {
            StoreLogic::insert_schedule($store->id);
        }
        Toastr::success(translate('messages.application_placed_successfully'));
        return back();
    }

}
