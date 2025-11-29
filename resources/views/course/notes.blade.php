@extends('index')
@section('content')

<div class="content-body " style="">
    <div class="container-fluid">
        <!-- row -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div>
                            <a href="javascript:;"  onclick="basic_modal('','manage_notes','Manage Notes')" data-bs-toggle="modal" data-bs-target="#basicModal" class="btn btn-primary" >
								+ Add Notes
							</a>
                        </div><br>
                        <div class="row form-material">
                            <div class="col-xl-3 col-xxl-3 col-md-6 mb-3">
                                <label class="form-label">Course</label>
                                <div class="dropdown bootstrap-select default-select form-control wide mb-3">
                                    <select class="default-select form-control wide mb-3">
                                        <option>Option 1</option>
                                        <option>Option 2</option>
                                        <option>Option 3</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-3 col-xxl-3 col-md-6 mb-3">
                                <label class="form-label">Subject</label>
                                <div class="dropdown bootstrap-select default-select form-control wide mb-3">
                                    <select class="default-select form-control wide mb-3">
                                        <option>Option 1</option>
                                        <option>Option 2</option>
                                        <option>Option 3</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-3 col-xxl-3 col-md-6 mb-3">
                                <label class="form-label">Topic</label>
                                <div class="dropdown bootstrap-select default-select form-control wide mb-3">
                                    <select class="default-select form-control wide mb-3">
                                        <option>Option 1</option>
                                        <option>Option 2</option>
                                        <option>Option 3</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-3 col-xxl-3 col-md-6 mb-3">
                                <button type="submit" class="btn btn-primary ">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
