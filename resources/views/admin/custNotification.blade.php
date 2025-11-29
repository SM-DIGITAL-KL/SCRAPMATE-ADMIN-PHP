@extends('index')
@section('content')
<div class="content-body ">
    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-12">
                @include('layouts.flashmessage')
                <div class="card">
                    <div class="card-body pb-xl-4 pb-sm-3 pb-0">
                        <form action="{{ route('sendCustNotification') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="cust_ids" id="cust_ids">
                            <div class="form-group row">
                                <div class="col-sm-2"></div>
                                <label for="customer" class="col-sm-2 col-form-label">Select Customer</label>
                                <div class="col-sm-6">
                                    <select class="form-control select2" multiple onchange="getCustomerIds(this)">
                                        @foreach($customer as $cust)
                                            <option value="{{ $cust->id }}">{{ $cust->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div><br>
                            <div class="form-group row">
                                <div class="col-sm-2"></div>
                                <label for="title" class="col-sm-2 col-form-label">Notification Title</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                            </div><br>
                        
                            <div class="form-group row">
                                <div class="col-sm-2"></div>
                                <label for="message" class="col-sm-2 col-form-label">Message</label>
                                <div class="col-sm-6">
                                    <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                                </div>
                            </div><br>
                        
                            <div class="form-group row">
                                <div class="col-sm-10 offset-sm-4">
                                    <button type="submit" class="btn btn-primary">Send Notification</button>
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
@section('contentjs')
<script>
    function getCustomerIds(selectElement) {
        var selectedIds = Array.from(selectElement.selectedOptions).map(option => option.value);
        // alert(selectedIds);
        $('#cust_ids').val(selectedIds.join(','));
    }
</script>
@endsection

