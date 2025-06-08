<?php
namespace App\Models;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasFactory, SoftDeletes;
    protected $fillable = [
        'name', 'email', 'password', 'created_by', 'updated_by', 'deleted_by'
    ];
    protected $hidden = [
        'password', 'remember_token',
    ];
    protected $dates = ['deleted_at'];

    public function roles() {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
    }

    // Metode baru untuk mendapatkan semua izin menu dari peran user
    public function getAllMenuPermissions()
    {
        $permissions = [];
        // Memuat relasi roles dan kemudian relasi menus dengan pivot permissions
        $roles = $this->roles()->with(['menus' => function($query) {
            $query->withPivot('can_view', 'can_read', 'can_create', 'can_update', 'can_delete');
        }])->get();

        foreach ($roles as $role) {
            foreach ($role->menus as $menu) {
                // Gunakan 'path' sebagai kunci unik untuk permissions
                $menuPath = $menu->path;

                // Hanya pertimbangkan menu dengan path yang valid (bukan null atau '#')
                if ($menuPath && $menuPath !== '#') {
                    // Inisialisasi jika belum ada untuk path ini
                    if (!isset($permissions[$menuPath])) {
                        $permissions[$menuPath] = [
                            'can_view' => false,
                            'can_read' => false,
                            'can_create' => false,
                            'can_update' => false,
                            'can_delete' => false,
                        ];
                    }

                    // Gabungkan permissions dari semua peran.
                    // Jika user memiliki banyak peran, izin akan menjadi TRUE jika setidaknya satu peran memiliki izin tersebut.
                    $permissions[$menuPath]['can_view']   = $permissions[$menuPath]['can_view']   || (bool)$menu->pivot->can_view;
                    $permissions[$menuPath]['can_read']   = $permissions[$menuPath]['can_read']   || (bool)$menu->pivot->can_read;
                    $permissions[$menuPath]['can_create'] = $permissions[$menuPath]['can_create'] || (bool)$menu->pivot->can_create;
                    $permissions[$menuPath]['can_update'] = $permissions[$menuPath]['can_update'] || (bool)$menu->pivot->can_update;
                    $permissions[$menuPath]['can_delete'] = $permissions[$menuPath]['can_delete'] || (bool)$menu->pivot->can_delete;
                }
            }
        }

        return $permissions;
    }

    public function hasRole($role) {
        if (is_array($role)) {
            foreach ($role as $r) { if ($this->roles->contains('name', $r)) { return true; } }
            return false;
        } return $this->roles->contains('name', $role);
    }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function deleter() { return $this->belongsTo(User::class, 'deleted_by'); }
}