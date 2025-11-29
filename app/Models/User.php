<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use App\Models\Shop;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public static function permission($user_type,$pagename,$id)
    {
        if ($user_type == 'A') {
            return true;
        } else {
            $role = DB::table('user_admins')->where('user_id',$id)->first();
            $page = DB::table('per_pages')->where('name', 'like', '%' . $pagename . '%')->first();
            $pagePermissions = explode(',', $role->page_permission);
            if (!empty($role->page_permission) || !empty($page)) {
                if (in_array($page->id, $pagePermissions)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
    }

    public static function get_lat_log_data() {
        $shop = Shop::select('id','lat_log','place')->where('status',2)->get();

    }
}
