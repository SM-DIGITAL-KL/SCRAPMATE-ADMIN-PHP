@extends('index')
@section('content')

<div class="content-body " style="">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div >
                            <a href="javascript:;"  onclick="basic_modal('','manage_subjects','Manage Subjects')" data-bs-toggle="modal" data-bs-target="#basicModal" class="btn btn-primary" >+ Add Subject</a>
                            <a href="javascript:;"  onclick="basic_modal('','manage_topics','Manage Topics')" data-bs-toggle="modal" data-bs-target="#basicModal" class="btn btn-primary" >+ Add Topics</a>
                        </div><br>
                        <div class="card-body p-0" >
                            <div class="default-tab">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#home"><i class="la la-home me-2"></i> Subjects</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="tab" href="#profile"><i class="la la-user me-2"></i> Topics</a>
                                    </li>
                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="home" role="tabpanel">
                                        <div class="pt-4">
                                            <div class="table-responsive " >
                                                <table id="example4" class="display table" >
                                                    <thead>
                                                        <tr>
                                                            <th>Sl.No</th>
                                                            <th>Course </th>
                                                            <th>Subject </th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>1</td>
                                                            <td>Test</td>
                                                            <td>Test Subject</td>
                                                            <td>
                                                                <div class="d-flex">
                                                                    <a href="#" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fas fa-pencil-alt"></i></a>
                                                                    <a href="#" class="btn btn-danger shadow btn-xs sharp"><i class="fa fa-trash"></i></a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="profile">
                                        <div class="pt-4">
                                            <div class="table-responsive " >
                                                <table id="example4" class="display table" >
                                                    <thead>
                                                        <tr>
                                                            <th>Sl.No</th>
                                                            <th>Course </th>
                                                            <th>Subject </th>
                                                            <th>Topic </th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>1</td>
                                                            <td>Test</td>
                                                            <td>Test Subject</td>
                                                            <td>Test Topic</td>
                                                            <td>
                                                                <div class="d-flex">
                                                                    <a href="#" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fas fa-pencil-alt"></i></a>
                                                                    <a href="#" class="btn btn-danger shadow btn-xs sharp"><i class="fa fa-trash"></i></a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

