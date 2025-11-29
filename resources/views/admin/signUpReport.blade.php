@extends('index')
@section('content')

<div class="content-body ">
    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-12">
                @include('layouts.flashmessage')
                <div class="card">
                    <div class="card-body pb-xl-4 pb-sm-3 pb-0">
                        <form action="{{ route('signUpReport') }}" method="post">
                            @csrf
                            <div class="row align-items-center">
                                <div class="col-xl-3 col-md-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" class="form-control" name="start_date" value="{{date('Y-m-d')}}" id="start_date" required>
                                </div>
                                <div class="col-xl-3 col-md-3">
                                    <label class="form-label">End Date</label>
                                    <input type="date" class="form-control" name="end_date" value="{{date('Y-m-d')}}" id="end_date" required>
                                </div>
                                <div class="col-xl-3 col-md-3">
                                    <label class="form-label">Users Type</label>
                                    <select class="form-control" name="user_type" id="user_type" required>
                                        <option value="">---  Select ---</option>
                                        <option value="S">Vendors</option>
                                        <option value="C">Customers</option>
                                        <option value="D">Door Step Buyers(Delivery Boy Under Vendors)</option>
                                    </select>
                                </div>
                                <div class="col-xl-3 col-3 d-flex align-items-center">
                                    <button type="submit" class="btn btn-primary" id="submit">Submit</button>
                                </div>
                            </div>  
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
