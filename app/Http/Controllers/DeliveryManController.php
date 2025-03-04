<?php

namespace App\Http\Controllers;

use App\Models\DeliveryMan;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\Admin;
use App\Models\BusinessSetting;
use Gregwar\Captcha\CaptchaBuilder;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class DeliveryManController extends Controller
{

    public function create()
    {
        $status = BusinessSetting::where('key', 'toggle_dm_registration')->first();
        if(!isset($status) || $status->value == '0')
        {
            Toastr::error(translate('messages.not_found'));
            return back();
        }

        $custome_recaptcha = new CaptchaBuilder;
        $custome_recaptcha->build();
        Session::put('six_captcha', $custome_recaptcha->getPhrase());

        return view('dm-registration', compact('custome_recaptcha'));
    }

    public function store(Request $request)
    {
        $status = BusinessSetting::where('key', 'toggle_dm_registration')->first();
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
//        } else if(session('six_captcha') != $request->custome_recaptcha)
//        {
//            Toastr::error(trans('messages.ReCAPTCHA Failed'));
//            return back();
//        }

        $request->validate([
            'f_name' => 'required|max:100',
            'l_name' => 'nullable|max:100',
            'identity_number' => 'required|max:30|unique:delivery_men', // v2.8.1
            'email' => 'required|unique:delivery_men',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|unique:delivery_men',
            'zone_id' => 'required',
            'vehicle_id' => 'required',
            'earning' => 'required',
            'dm_address' => 'required', // mainul-v2.12
            'dm_address_proof' => 'required',   // mainul-v2.12
            'password' => ['required', Password::min(8)/*->mixedCase()->letters()->numbers()->symbols()->uncompromised()*/],
        ], [
            'f_name.required' => translate('messages.first_name_is_required'),
            'zone_id.required' => translate('messages.select_a_zone'),
            'vehicle_id.required' => translate('messages.select_a_vehicle'),
            'earning.required' => translate('messages.select_dm_type')
        ]);

        if ($request->has('image')) {
            $image_name = Helpers::upload('delivery-man/', 'png', $request->file('image'));
        } else {
            $image_name = 'def.png';
        }

        $id_img_names = [];
        if (!empty($request->file('identity_image'))) {
            foreach ($request->identity_image as $img) {
                $identity_image = Helpers::upload('delivery-man/', 'png', $img);
                array_push($id_img_names, ['img'=>$identity_image, 'storage'=> Helpers::getDisk()]);
            }
            $identity_image = json_encode($id_img_names);
        } else {
            $identity_image = json_encode([]);
        }

        $dm_img_names = [];
        if (!empty($request->file('dm_address_proof'))) {
            foreach ($request->dm_address_proof as $imgx) {
                $dm_address_proof = Helpers::upload('delivery-man/address-proof/', 'png', $imgx);
                array_push($dm_img_names, ['img'=>$dm_address_proof, 'storage'=> Helpers::getDisk()]);
            }
            $dm_address_proof = json_encode($dm_img_names);
        } else {
            $dm_address_proof = json_encode([]);
        }

        $dm = New DeliveryMan();
        $dm->f_name = $request->f_name;
        $dm->l_name = $request->l_name;
        $dm->email = $request->email;
        $dm->phone = $request->phone;
        $dm->identity_number = $request->identity_number;
        $dm->identity_type = $request->identity_type;
        $dm->vehicle_id = $request->vehicle_id;
        $dm->zone_id = $request->zone_id;
        $dm->dm_address = $request->dm_address; // mainul-v2.12
        $dm->identity_image = $identity_image;
        $dm->dm_address_proof = $dm_address_proof;  // mainul-v2.12
        $dm->image = $image_name;
        $dm->active = 0;
        $dm->earning = $request->earning;
        $dm->password = bcrypt($request->password);
        $dm->application_status= 'pending';
        $dm->save();




        try{
            $admin= Admin::where('role_id', 1)->first();

            if(config('mail.status') &&  Helpers::get_mail_status('registration_mail_status_dm') == '1' && Helpers::getNotificationStatusData('deliveryman','deliveryman_registration','mail_status')  ){
                Mail::to($request->email)->send(new \App\Mail\DmSelfRegistration('pending', $dm->f_name.' '.$dm->l_name));
            }
            if(config('mail.status') && Helpers::get_mail_status('dm_registration_mail_status_admin') == '1' && Helpers::getNotificationStatusData('admin','deliveryman_self_registration','mail_status')) {
                Mail::to($admin['email'])->send(new \App\Mail\DmRegistration('pending', $dm->f_name.' '.$dm->l_name));
            }
        }catch(\Exception $ex){
            info($ex->getMessage());
        }
        Toastr::success(translate('messages.application_placed_successfully'));
        return back();
    }

    public function download_dm_agereement(){
        $dm_agreement = \App\Models\BusinessSetting::where('key', 'dm_agreement')->first();
        $fileName=$dm_agreement->value;
        $path = '/agereement/';
        if (Storage::disk('public')->exists($path . $fileName)) {
            return Response::download(storage_path('app/public/agereement/' . $fileName));
        }
    }
}
