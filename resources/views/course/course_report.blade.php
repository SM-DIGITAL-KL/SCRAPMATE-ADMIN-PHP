@extends('index')
@section('content')

<div class="content-body " style="">
    <div class="container-fluid">
        <!-- row -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row form-material">
                            <div class="col-xl-3 col-xxl-3 col-md-6 mb-3">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control" placeholder="2017-06-04" id="mdate">
                            </div>
                            <div class="col-xl-3 col-xxl-3 col-md-6 mb-3">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" placeholder="2017-06-04" id="mdate">
                            </div>
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


