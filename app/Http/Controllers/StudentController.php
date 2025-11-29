<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function student()
    {
        $data['pagename'] = 'Student';
        return view('student/student', $data);
    }
    public function manage_student()
    {
        $data['pagename'] = 'Student';
        return view('student/manage_student', $data);
    }
    public function student_payment()
    {
        $data['pagename'] = 'Student Payment';
        return view('student/student_payment', $data);
    }
    public function student_activation()
    {
        $data['pagename'] = 'Student Activation';
        return view('student/student_activation', $data);
    }
}
