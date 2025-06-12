<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    // Nama tabel yang terkait dengan model
    protected $table = 'app_settings';

    // Kolom yang dapat diisi secara massal
    protected $fillable = [
        'application_name',
        'application_logo',
    ];

    // Menyembunyikan kolom timestamp jika tidak diperlukan dalam output JSON
    // protected $hidden = [
    //     'created_at',
    //     'updated_at',
    // ];
}