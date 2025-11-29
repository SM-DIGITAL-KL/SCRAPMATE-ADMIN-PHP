<!-- load css -->
@include('layouts.header')

    <!-- load dynamic body -->
    @yield('content')

<!-- load js -->
@include('layouts.footer')
{{-- <script>
	jQuery(document).ready(function () {
		setTimeout(function () {
			dlabSettingsOptions.version = 'dark';
			new dlabSettings(dlabSettingsOptions);
		}, 1500)
	});
</script> --}}
@include('layouts.modal')
@yield('contentjs')
