<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function report()
    {
        $data['pagename'] = 'Reports';
        return view('report/report', $data);
    }
}
