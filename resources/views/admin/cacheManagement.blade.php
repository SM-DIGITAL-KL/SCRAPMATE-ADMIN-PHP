@extends('index')
@section('content')

<div class="content-body ">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        @include('layouts.flashmessage')
                        <h4 class="card-title">Cache Management</h4>
                        <p class="text-muted">Clear Redis cache for specific user types to refresh dashboard data</p>
                        <hr>
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card border-primary">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary">
                                            <i class="fa fa-building"></i> B2B Users Cache
                                        </h5>
                                        <p class="card-text">Clear cache for all B2B (Shop Owner) users. This will refresh their dashboard data.</p>
                                        <button type="button" class="btn btn-primary" onclick="clearCache('b2b')" id="btnClearB2B">
                                            <i class="fa fa-trash"></i> Clear B2B Cache
                                        </button>
                                        <div id="resultB2B" class="mt-2"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <div class="card border-success">
                                    <div class="card-body">
                                        <h5 class="card-title text-success">
                                            <i class="fa fa-store"></i> B2C Users Cache
                                        </h5>
                                        <p class="card-text">Clear cache for all B2C (Retailer) users. This will refresh their dashboard data.</p>
                                        <button type="button" class="btn btn-success" onclick="clearCache('b2c')" id="btnClearB2C">
                                            <i class="fa fa-trash"></i> Clear B2C Cache
                                        </button>
                                        <div id="resultB2C" class="mt-2"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <div class="card border-info">
                                    <div class="card-body">
                                        <h5 class="card-title text-info">
                                            <i class="fa fa-users"></i> SR Users Cache
                                        </h5>
                                        <p class="card-text">Clear cache for all SR (Shop + Recycler) users. This will refresh their dashboard data.</p>
                                        <button type="button" class="btn btn-info" onclick="clearCache('sr')" id="btnClearSR">
                                            <i class="fa fa-trash"></i> Clear SR Cache
                                        </button>
                                        <div id="resultSR" class="mt-2"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <div class="card border-warning">
                                    <div class="card-body">
                                        <h5 class="card-title text-warning">
                                            <i class="fa fa-truck"></i> Door Step Buyers Cache
                                        </h5>
                                        <p class="card-text">Clear cache for all Door Step Buyers (Delivery) users. This will refresh their dashboard data.</p>
                                        <button type="button" class="btn btn-warning" onclick="clearCache('d')" id="btnClearD">
                                            <i class="fa fa-trash"></i> Clear Door Buyers Cache
                                        </button>
                                        <div id="resultD" class="mt-2"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-12 mb-4">
                                <div class="card border-danger">
                                    <div class="card-body">
                                        <h5 class="card-title text-danger">
                                            <i class="fa fa-exclamation-triangle"></i> Clear All Caches
                                        </h5>
                                        <p class="card-text"><strong>Warning:</strong> This will clear cache for all user types (B2B, B2C, SR, and Door Step Buyers). Use with caution.</p>
                                        <button type="button" class="btn btn-danger" onclick="clearCache('all')" id="btnClearAll">
                                            <i class="fa fa-trash"></i> Clear All Caches
                                        </button>
                                        <div id="resultAll" class="mt-2"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('contentjs')
<script>
function clearCache(userType) {
    // Disable button and show loading
    const btnId = `btnClear${userType === 'b2b' ? 'B2B' : (userType === 'b2c' ? 'B2C' : (userType === 'sr' ? 'SR' : (userType === 'd' ? 'D' : 'All')))}`;
    const resultId = `result${userType === 'b2b' ? 'B2B' : (userType === 'b2c' ? 'B2C' : (userType === 'sr' ? 'SR' : (userType === 'd' ? 'D' : 'All')))}`;
    const btn = document.getElementById(btnId);
    const resultDiv = document.getElementById(resultId);
    
    if (!btn || !resultDiv) return;
    
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Clearing...';
    resultDiv.innerHTML = '<div class="alert alert-info">Clearing cache, please wait...</div>';
    
    // Make API call
    fetch('{{ route("clearCache") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            userType: userType
        })
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        if (data.status === 'success') {
            resultDiv.innerHTML = `<div class="alert alert-success">
                <strong>Success!</strong> ${data.msg}<br>
                <small>Deleted ${data.data?.deletedCount || 0} cache keys</small>
            </div>`;
        } else {
            resultDiv.innerHTML = `<div class="alert alert-danger">
                <strong>Error!</strong> ${data.msg || 'Failed to clear cache'}
            </div>`;
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        resultDiv.innerHTML = `<div class="alert alert-danger">
            <strong>Error!</strong> ${error.message || 'Failed to clear cache'}
        </div>`;
        console.error('Error clearing cache:', error);
    });
}
</script>
@endsection






