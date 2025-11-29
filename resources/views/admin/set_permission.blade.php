@extends('index')
@section('content')

<div class="content-body ">
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
			<div class="card">
				<div class="card-body pb-xl-4 pb-sm-3 pb-0">
					<div class="row">
                        <div class="col-xl-4 col-3"></div>
                        <div class="col-xl-4 col-6 text-center">
                            <div class="dropdown bootstrap-select default-select form-control wide mb-3">
                                <select name="users" class="default-select form-control wide mb-3" style="text-align: center;" onchange="getuser(this.value);">
                                    <option class="text-center" value="">---select---</option>
                                    @foreach ($users as $u)
                                    <option class="text-center" value="{{$u->id}}" {{ $user_id == $u->id ? 'selected' : '' }}>{{$u->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
					</div>
				</div>
			</div>
		</div>
        @if (!empty($user_data))
        <div class="col-xl-12">
			<div class="card">
				<div class="card-body pb-xl-4 pb-sm-3 pb-0">
                    <div class="table-responsive">
                        <form action="{{ url('/store_user_per') }}" method="POST" class="needs-validation" novalidate>
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $user_id }}">
                            <table class="table header-border table-hover verticle-middle">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Pages</th>
                                        <th scope="col">Permission</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($permission as $per)
                                        @php
                                            $storedPermissions = explode(',', $user_data->page_permission);
                                            $checked = in_array($per->id, $storedPermissions) ? 'checked' : '';
                                        @endphp
                                        <tr>
                                            <th>{{ $loop->iteration }}</th>
                                            <td><b>{{ $per->name }}</b></td>
                                            <td>
                                                <div class="form-check custom-checkbox mb-3">
                                                    <input type="checkbox" name="permission-{{ $per->id }}"  value="{{ $per->id }}"  class="form-check-input" {{ $checked }}>
                                                    {{-- <input type="checkbox" name="permission[]"  value="{{ $per->id }}"  class="form-check-input" {{ $checked }}> --}}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </form>                        
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
</div>

@endsection
@section('contentjs')
<script>
    function getuser( id ) {
        var fullurl = "{{ route('set_permission', ['id' => '']) }}/" + id;
        window.location.href = fullurl;
    };
</script>
@endsection
