<?php

namespace Modules\Rental\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use Illuminate\Queue\SerializesModels;
use Modules\Rental\Entities\RentalEmailTemplate;

class ProviderWithdrawRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $wallet;
    protected $status;

    public function __construct($status,$wallet)
    {
        $this->wallet = $wallet;
        $this->status = $status;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $company_name = BusinessSetting::where('key', 'business_name')->first()->value;
        $status = $this->status;
        if($status == 'approved'){
            $data=RentalEmailTemplate::where('type','provider')->where('email_type', 'withdraw_approve')->first();
        }elseif($status == 'denied'){
            $data=RentalEmailTemplate::where('type','provider')->where('email_type', 'withdraw_deny')->first();
        }else{
            $data=RentalEmailTemplate::where('type','admin')->where('email_type', 'withdraw_request')->first();
        }
        $template=$data?$data->email_template:6;
        $wallet = $this->wallet;
        $store_name = $wallet->vendor->f_name;
        $title = Helpers::text_variable_data_format( value:$data['title']??'',store_name:$store_name??'',transaction_id:$transaction_id??'');
        $body = Helpers::text_variable_data_format( value:$data['body']??'',store_name:$store_name??'',transaction_id:$transaction_id??'');
        $footer_text = Helpers::text_variable_data_format( value:$data['footer_text']??'',store_name:$store_name??'',transaction_id:$transaction_id??'');
        $copyright_text = Helpers::text_variable_data_format( value:$data['copyright_text']??'',store_name:$store_name??'',transaction_id:$transaction_id??'');
        return $this->subject(translate('Provider_Withdraw_Request'))->view('email-templates.rental.new-email-format-'.$template, ['company_name'=>$company_name,'data'=>$data,'title'=>$title,'body'=>$body,'footer_text'=>$footer_text,'copyright_text'=>$copyright_text,'wallet'=>$wallet,'transaction_id'=>$wallet->id,'time'=>$wallet->created_at,'amount'=>$wallet->amount]);
    }
}
