@extends('index')
@section('content')
<div class="content-body " style="">
	<div class="container-fluid">
		<div class="col-xl-12">
			<div class="card">
                <div class="card-body">
                    @include('layouts.flashmessage')
                    <div class="text-end">
                        <a href="javascript:;"  onclick="basic_modal('','updateAppVersion','Update App Version')" data-bs-toggle="modal" data-bs-target="#basicModal" class="btn btn-success" >Update App Version</a>
                    </div><br>
					<div class="basic-form">
                        @php
                            $userName = $currentUser->name ?? session('user_name', '');
                            $userEmail = $currentUser->email ?? session('user_email', '');
                            $userContact = $currentUser->mob_num ?? ($currentUser->phone ?? '');
                        @endphp
						<form method="POST" action="{{ route('manage_site') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="mb-3 col-xl-4">
                                    <label for="formFile" class="form-label">Logo</label>
                                    <input class="form-control dropify" type="file" id="formFile" name="logo" accept="image/jpeg,image/jpg,image/png" data-default-file="{{ isset($profile->logo) ? $profile->logo : '' }}">
                                </div>
                                <div class="mb-3 col-xl-4">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" placeholder="Site Name" name="name" value="{{ old('name', $userName !== '' ? $userName : (isset($profile->name) ? $profile->name : '')) }}">
                                </div>
                                <div class="mb-3 col-xl-4">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" placeholder="admin@example.com" name="email" value="{{ old('email', $userEmail !== '' ? $userEmail : (isset($profile->email) ? $profile->email : '')) }}" autocomplete="off">
                                </div>
                                <div class="mb-3 col-xl-4">
                                    <label class="form-label">Contact Number</label>
                                    <input type="tel" class="form-control" placeholder="Number" name="contact" value="{{ old('contact', $userContact !== '' ? $userContact : (isset($profile->contact) ? $profile->contact : '')) }}">
                                </div>
                                <div class="mb-3 col-xl-4">
                                    <label class="form-label">Address</label>
                                    <input type="text" class="form-control" placeholder="Address" name="address" value="{{ old('address', isset($profile->address) ? $profile->address : '') }}">
                                </div>
                                <div class="mb-3 col-xl-4">
                                    <label class="form-label">Location</label>
                                    <input type="text" class="form-control" placeholder="Location" name="location" value="{{ old('location', isset($profile->location) ? $profile->location : '') }}">
                                </div>
                            </div>
							<button type="submit" class="btn btn-primary">Submit</button>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>


@endsection
