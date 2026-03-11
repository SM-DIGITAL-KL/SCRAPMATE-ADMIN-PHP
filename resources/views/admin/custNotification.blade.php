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
                            @php
                                $lockedZone = $logged_in_zone ?? null;
                            @endphp
                            <div class="form-group row">
                                <div class="col-sm-2"></div>
                                <label for="zone_code" class="col-sm-2 col-form-label">Target Zone</label>
                                <div class="col-sm-6">
                                    @if($lockedZone)
                                        <select class="form-control" id="zone_code" disabled>
                                            <option value="{{ $lockedZone }}" selected>{{ $lockedZone }}</option>
                                        </select>
                                        <input type="hidden" name="zone_code" value="{{ $lockedZone }}">
                                        <small class="text-primary">Zone is locked for your login.</small>
                                    @else
                                        <select class="form-control" name="zone_code" id="zone_code" required>
                                            <option value="">Select Zone</option>
                                            @for($i = 1; $i <= 48; $i++)
                                                @php $z = 'Z' . str_pad((string) $i, 2, '0', STR_PAD_LEFT); @endphp
                                                <option value="{{ $z }}">{{ $z }}</option>
                                            @endfor
                                        </select>
                                        <small class="text-muted">Notification will be sent only to customer app users in this zone.</small>
                                    @endif
                                </div>
                            </div><br>

                            <div class="form-group row">
                                <div class="col-sm-2"></div>
                                <label for="message_template" class="col-sm-2 col-form-label">Templates</label>
                                <div class="col-sm-6">
                                    <select class="form-control" id="message_template">
                                        <option value="">Select Template</option>
                                        <option value="welcome_customers">Welcome Greeting (Sample 1)</option>
                                        <option value="pickup_update">Pickup Update (Sample 2)</option>
                                        <option value="zonal_franchise">Zonal Franchise Invite</option>
                                    </select>
                                    <small class="text-muted">Selecting a template fills title/message. You can edit before sending.</small>
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
    const templateMap = {
        welcome_customers: {
            title: 'Greetings from ScrapMate',
            message: 'Hello Customer, welcome to ScrapMate zone services. Thank you for choosing eco-friendly recycling in your area.'
        },
        pickup_update: {
            title: 'Zone Pickup Update',
            message: 'Dear Customer, pickup services are active in your zone. Please place your scrap request and our nearby partner will reach you soon.'
        },
        zonal_franchise: {
            title: 'Zonal Franchise Operations',
            message: 'We are now welcoming Zonal Franchise Operations. If you are interested in becoming a zonal franchise partner, please message us at 7356468251.'
        }
    };

    $(document).ready(function(){
        $('#message_template').change(function(){
            const key = $(this).val();
            if (!key || !templateMap[key]) return;
            $('#title').val(templateMap[key].title);
            $('#message').val(templateMap[key].message);
        });
    });
</script>
@endsection

