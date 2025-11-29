@extends('index')
@section('content')

<div class="content-body " style="">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <a href="javascript:;"  onclick="large_modal('','manage_questions','Manage Questions')" data-bs-toggle="modal" data-bs-target=".bd-example-modal-lg" class="btn btn-primary" >   + Add Questions</a>
                        <a href="javascript:;"  onclick="large_modal('','import_questions','Import Questions')" data-bs-toggle="modal" data-bs-target=".bd-example-modal-lg" class="btn btn-primary" >   + Import Questions</a>
                        <div class="card-body p-0" >
                            <div class="table-responsive">
                                <table id="example4" class="display table" >
                                    <thead>
                                        <tr>
                                            <th>sl.No</th>
                                            <th>Course</th>
                                            <th>Subject & Topic</th>
                                            <th>Questions</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>1</td>
                                            <td>Test</td>
                                            <td>Test Subject<small>Test Topic</small></td>
                                            <td>Question..................</td>
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
