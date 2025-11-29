<div class="form-validation">
    <form action="{{ route ('manage_agent') }}" method="POST" class="needs-validation" validate>
        @csrf
        <input type="hidden" name="user_id" value="{{$shop->id ?? ''}}">
        <div class="row">
            <div class="col-lg-6">
                <label class="form-label" for="validationCustom01">Name<span class="text-danger">*</span></label>
                <input type="text" name="shopname" value="{{$shop->shopname ?? ''}}" class="form-control" id="validationCustom01" placeholder="Enter name of shop" required>
                <div class="invalid-feedback">Please enter a name of shop.</div>
            </div>
            <div class="col-lg-6">
                <label class="form-label" for="validationCustom02">Name of owner<span class="text-danger">*</span></label>
                <input type="text" name="ownername" value="{{$shop->ownername ?? ''}}" class="form-control" id="validationCustom02" placeholder="Enter name of owner" required>
                <div class="invalid-feedback">Please enter name of owner.</div>
            </div>
            <div class="col-lg-6">
                <label class="form-label" for="validationCustom02">Contact Number<span class="text-danger">*</span></label>
                <input type="number" name="contact" value="{{$shop->contact ?? ''}}" class="form-control" id="validationCustom02" placeholder="Enter name of owner" required>
                <div class="invalid-feedback">Please enter contact number.</div>
            </div>
            <div class="col-lg-6">
                <label class="form-label" for="validationCustom02">Address<span class="text-danger">*</span></label>
                <textarea type="number" name="address" class="form-control" id="validationCustom02" placeholder="Enter address" required>{{$shop->address ?? ''}}</textarea>
                <div class="invalid-feedback">Please enter address.</div>
            </div>
            @if (empty($shop))
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
