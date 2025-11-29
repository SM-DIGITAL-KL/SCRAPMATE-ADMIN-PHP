<div class="form-validation">
    <form action="{{ route ('manage_users') }}" method="POST" class="needs-validation" validate>
        @csrf
        <input type="hidden" name="user_id" value="{{$user->id ?? ''}}">
        <div class="row">
            <div class="col-lg-6">
                <label class="form-label" for="validationCustom01">Name<span class="text-danger">*</span></label>
                <input type="text" name="names" value="{{$user->name ?? ''}}" class="form-control" id="validationCustom01" placeholder="Enter a name" required>
                <div class="invalid-feedback">Please enter a Name.</div>
            </div>
            <div class="col-lg-6">
                <label class="form-label" for="validationCustom02">Phone Number<span class="text-danger">*</span></label>
                <input type="number" name="phone" value="{{$user->phone ?? ''}}" class="form-control" id="validationCustom02" placeholder="Enter phone number" required>
                <div class="invalid-feedback">Please enter phone number.</div>
            </div>
            @if (empty($user))
            <div class="col-lg-6">
                <label class="form-label" for="validationCustom03">Email<span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" id="validationCustom03" placeholder="Enter a email" required>
                <div class="invalid-feedback">Please enter a Email.</div>
            </div>
            <div class="col-lg-6">
                <label class="form-label" for="validationCustom04">Password<span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control" id="validationCustom04" placeholder="Enter password" required>
                <div class="invalid-feedback">Please enter password.</div>
            </div>
            @endif
        </div><br>
        <center><button type="submit" class="btn btn-primary ">Submit</button></center>
    </form>
</div>
