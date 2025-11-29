@extends('index')
@section('content')

<div class="content-body " style="">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <a href="javascript:;"  onclick="large_modal('','manage_student','Manage Student')" data-bs-toggle="modal" data-bs-target=".bd-example-modal-lg" class="btn btn-primary" >
                            + Add Student
                        </a><br>
                        <div class="card-body p-0" >
                            <div class="table-responsive">
                                <table id="example3" class="display table" style="width: 100%">
                                    <thead>
                                        <tr>
                                            <th>Sl.No</th>
                                            <th>Name</th>
                                            <th>E-mail</th>
                                            <th>Gender</th>
                                            <th>Phone</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>1</td>
                                            <td>Fiona Green</td>
                                            <td>Fiona@gmqail.com</td>
                                            <td>Male</td>
                                            <td>56454684</td>
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

@endsection




