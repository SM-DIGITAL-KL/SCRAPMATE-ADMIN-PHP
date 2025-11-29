@if ($message = Session::get('success'))
<div class="alert alert-success alert-dismissible fade show">
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close">
    </button>
    <strong>Success!</strong> {{$message}}.
</div>
@endif
@if ($message = Session::get('error'))
<div class="alert alert-danger alert-dismissible fade show">
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close">
    </button>
    <strong>Error!</strong> {{$message}}.
</div>
@endif
@if ($message = Session::get('warning'))
<div class="alert alert-warning alert-dismissible fade show">
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close">
    </button>
    <strong>Warning!</strong> {{$message}}.
</div>
@endif
@if ($message = Session::get('info'))
<div class="alert alert-info alert-dismissible fade show">
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close">
    </button>
    <strong>Info!</strong> {{$message}}.
</div>
@endif

@if ($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close">
    </button>
     @foreach ($errors->all() as $error)
        <li><strong>Error!</strong> {{$message}}.</li>
    @endforeach
</div>
@endif
