<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = [
        'label', 'icon', 'path', 'order', 'is_main_menu', 'parent_id', 'is_logout'
    ];

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('order');
    }

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'menu_role', 'menu_id', 'role_id')
                    ->withPivot('can_view', 'can_read', 'can_create', 'can_update', 'can_delete')
                    ->withTimestamps(); // Tambahkan withPivot()
    }
}