<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SubSchoolController extends Controller
{
    public function subschool()
    {
        $data['pagename'] = 'Sub Schools';
        return view('admin/subschool');
    }
    public function manage_schools()
    {
        echo 'hai';
    }
}
