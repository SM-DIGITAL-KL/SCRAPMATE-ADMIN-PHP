<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class file_upload extends Model
{   
    use HasFactory;

    public static function upload_image($name_file, $path_file) {
        $profile_photo_name = time() . rand(1, 1000) . '.' . $name_file->getClientOriginalExtension();
        $name_file->move($path_file, $profile_photo_name);
        return $profile_photo_name; 
    }
    
}
