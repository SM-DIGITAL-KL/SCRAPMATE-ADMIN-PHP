<div class="form-validation">
    <form action="{{ route('subPackages.create') }}" method="POST" class="needs-validation" validate>
        @csrf
        <div class="row">
            <div class="col-lg-6">
                <label class="form-label" for="validationCustom01">Package Name<span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" id="validationCustom01" placeholder="Enter a Package Name" required>
                <div class="invalid-feedback">Please enter a Package Name.</div>
            </div>
            <div class="col-lg-6">
                <label class="form-label" for="validationCustom01">Package Display Name<span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="displayname" id="validationCustom01" placeholder="Enter a Package Display Name" required>
                <div class="invalid-feedback">Please enter a Package Display Name.</div>
            </div>
            <div class="col-lg-6">
                <label class="form-label" for="validationCustom01">Type<span class="text-danger">*</span></label>
                <select class="default-select  form-control wide" name="type" id="validationCustom01" required>
                    <option value="">Select</option>
                    <option value="1">Free</option>
                    <option value="2">Paid</option>
                </select>
            </div>
            <div class="col-lg-6">
                <label class="form-label" for="validationCustom01">Package Duration (In Days)<span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="validationCustom01" placeholder="Enter Package Duration" name="duration" required>
                <div class="invalid-feedback">Please enter Package Duration</div>
            </div>
            
            <div class="col-lg-6">
                <label class="form-label" for="validationCustom01">Price<span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="validationCustom01" name="price" placeholder="Enter a price" required>
                <div class="invalid-feedback">Please enter a price.</div>
            </div>
            <div class="col-lg-6">
                <label class="form-label" for="validationCustom01">Order<span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="validationCustom01" name="order" placeholder="Enter Order" required>
                <div class="invalid-feedback">Please enter Order.</div>
            </div>
        </div><br>
        <center><button type="submit" class="btn btn-primary ">Submit</button></center>
    </form>
</div>
