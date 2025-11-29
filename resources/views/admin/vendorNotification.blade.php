@extends('index')
@section('content')
<div class="content-body ">
    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-12">
                @include('layouts.flashmessage')
                <div class="card">
                    <div class="card-body pb-xl-4 pb-sm-3 pb-0">
                        <form action="{{ route('sendVendorNotification') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group row">
                                <div class="col-sm-2"></div>
                                <label for="customer" class="col-sm-2 col-form-label">Select Criteria ( <span class="text-danger">Optional </span>)</label>
                                <div class="col-sm-6">
                                    <select class="select2" name="criteria" id="criteria">
                                        <option value="">Select criteria</option>
                                        <option value="1">Vendors with no shop images added ({{ $criteria_counts['1'] ?? 0 }})</option>
                                        <option value="2">Vendors with no categories added ({{ $criteria_counts['2'] ?? 0 }})</option>
                                        <option value="3">Vendors with no items added ({{ $criteria_counts['3'] ?? 0 }})</option>
                                    </select>
                                </div>
                            </div><br>

                            <div class="form-group row" id="shopdiv">
                                <div class="col-sm-2"></div>
                                <label for="shop" class="col-sm-2 col-form-label">Select Vendor</label>
                                <div class="col-sm-6">
                                    <select class="form-control select2" multiple onchange="getVendorIds(this)">
                                        @foreach($shops as $sh)
                                            <option value="{{ $sh->id }}">{{ $sh->name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-primary">Total Vendor Count = {{$shops_count}}</small>
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
                            <input type="hidden" name="vendor_ids" id="vendor_ids">
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
    function getVendorIds(selectElement) {
        var selectedIds = Array.from(selectElement.selectedOptions).map(option => option.value);
        // alert(selectedIds);
        $('#vendor_ids').val(selectedIds.join(','));
    }

    $(document).ready(function(){
        $('#criteria').change(function(){
            if($(this).val() !== ''){
                $('#shopdiv').hide();
            }else{
                $('#shopdiv').show();
            }
        });
    });
</script>
@endsection

