<?php

namespace App\Mail;

use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VendorSelfRegistration extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $status;
    protected $name;
    protected $type;

    public function __construct($status, $name, $type = 'store')
    {
        $this->status = $status;
        $this->name = $name;
        $this->type = $type;
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
            $data=EmailTemplate::where('type','store')->where('email_type', 'approve')->first();
        }elseif($status == 'denied'){
            $data=EmailTemplate::where('type','store')->where('email_type', 'deny')->first();
        }else{
            $data=EmailTemplate::where('type','store')->where('email_type', 'registration')->first();
        }
        $template=$data?$data->email_template:5;
        $url = '';
        $store_name = $this->name;
        $store_type = $this->type;
        $title = Helpers::text_variable_data_format( value:$data['title']??'',store_name:$store_name??'');
        $body = Helpers::text_variable_data_format( value:$data['body']??'',store_name:$store_name??'');
        $footer_text = Helpers::text_variable_data_format( value:$data['footer_text']??'',store_name:$store_name??'');
        $copyright_text = Helpers::text_variable_data_format( value:$data['copyright_text']??'',store_name:$store_name??'');
        return $this->subject(translate($store_type == 'company'? 'New_company_Registration' : 'New_Store_Registration'))->view('email-templates.new-email-format-'.$template, ['company_name'=>$company_name,'data'=>$data,'title'=>$title,'body'=>$body,'footer_text'=>$footer_text,'copyright_text'=>$copyright_text,'url'=>$url]);
    }
}
