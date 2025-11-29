<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function courses_category()
    {
        $data['pagename'] = 'Courses Category';
        return view('course/courses_category', $data);
    }
    public function manage_category()
    {
        $display = '<div class="card-body">
        <div class="form-validation">
            <form class="needs-validation" validate>
                <div class="row">
                    <label class="form-label" for="validationCustom01">Category Name<span class="text-danger">*</span></label>
                    <div class="col-lg-8">
                        <input type="text" class="form-control" id="validationCustom01" placeholder="Enter a username.." required>
                        <div class="invalid-feedback">Please enter a Name.</div>
                    </div>
                    <div class="col-lg-4 ms-auto">
                    <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>';
        echo $display;
    }
    public function courses()
    {
        $data['pagename'] = 'Courses';
        return view('course/courses', $data);
    }
    public function manage_courses()
    {
        $data['pagename'] = 'Courses';
        return view('course/manage_courses', $data);
    }
    public function course_report()
    {
        $data['pagename'] = 'Course Report';
        return view('course/course_report', $data);
    }
    public function sub_topic_list()
    {
        $data['pagename'] = 'Subjects & Topics';
        return view('course/sub_topic_list', $data);
    }
    public function manage_subjects()
    {
        $data['pagename'] = 'Subjects & Topics';
        return view('course/manage_subjects', $data);
    }
    public function manage_topics()
    {
        $data['pagename'] = 'Subjects & Topics';
        return view('course/manage_topics', $data);
    }
    public function videos()
    {
        $data['pagename'] = 'Videos';
        return view('course/videos', $data);
    }
    public function manage_videos()
    {
        $data['pagename'] = 'Videos';
        return view('course/manage_videos', $data);
    }
    public function notes()
    {
        $data['pagename'] = 'Notes';
        return view('course/notes', $data);
    }
    public function manage_notes()
    {
        $data['pagename'] = 'Notes';
        return view('course/manage_notes', $data);
    }
    public function audios()
    {
        $data['pagename'] = 'Audios';
        return view('course/audios', $data);
    }
    public function manage_audios()
    {
        $data['pagename'] = 'Audios';
        return view('course/manage_audios', $data);
    }
    public function assignment()
    {
        $data['pagename'] = 'Assignment';
        return view('course/assignment', $data);
    }
    public function manage_assignment()
    {
        $data['pagename'] = 'Assignment';
        return view('course/manage_assignment', $data);
    }
}
