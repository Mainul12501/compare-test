<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class TestimonialController extends Controller
{
    // v2.8.1 checked
    function get_tetimonial_lists()
    {
       $testimonials = \App\Models\BusinessSetting::where(['key'=>'testimonial'])->first();
       $testimonials = isset($testimonials->value)?json_decode($testimonials->value, true):[];
        return response()->json($testimonials,200);
    }
}
