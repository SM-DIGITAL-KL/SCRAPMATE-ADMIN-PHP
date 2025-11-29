<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function vendors()
    {
        $data['pagename'] = 'Vendor Manage';
        return view('vendors/vendors', $data);
    }
    public function manage_vendors()
    {
        $data['pagename'] = 'Vendor Manage';
        return view('vendors/manage_vendors', $data);
    }
}
