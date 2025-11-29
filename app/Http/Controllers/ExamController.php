<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function exams()
    {
        $data['pagename'] = 'Exams';
        return view('exam/exams', $data);
    }
    public function manage_exams()
    {
        return view('exam/manage_exams');
    }
    public function questions()
    {
        $data['pagename'] = 'Questions';
        return view('exam/questions', $data);
    }
    public function manage_questions()
    {
        $data['pagename'] = 'Questions';
        return view('exam/manage_questions', $data);
    }
    public function import_questions()
    {
        $data['pagename'] = 'Questions';
        return view('exam/import_questions', $data);
    }
    public function assesment()
    {
        $data['pagename'] = 'Assesment';
        return view('exam/assesment', $data);
    }
}
