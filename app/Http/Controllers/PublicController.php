<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;

class PublicController extends Controller
{
    public function appName()
    {
        $appSetting = AppSetting::firstOrCreate(
            ['id' => 1],
            ['application_name' => 'Hemodialysis SI', 'application_logo' => null]
        );

        return response()->json([
            'application_name' => $appSetting->application_name,
            'application_logo' => $appSetting->application_logo,
        ]);
    }
}
