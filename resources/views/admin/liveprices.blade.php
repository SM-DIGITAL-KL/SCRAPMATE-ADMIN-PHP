@extends('index')
@section('content')

<div class="content-body">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <!-- Header Section -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h4 class="card-title mb-1">Live Scrap Prices</h4>
                                <p class="text-muted mb-0">Real-time scrap prices from various locations across India</p>
                                @if(isset($lastUpdated))
                                    <small class="text-muted"><i class="fa fa-clock-o"></i> {{ $lastUpdated }}</small>
                                @endif
                            </div>
                            <div class="d-flex gap-2">
                                <span class="badge bg-success align-self-center">Total: {{ $totalCount ?? $prices->count() }} items</span>
                                <form method="POST" action="{{ route('liveprices.scrape') }}" onsubmit="return confirm('This will refresh prices from all sources and may take 2-5 minutes. Continue?');">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-refresh"></i> Refresh Prices
                                    </button>
                                </form>
                            </div>
                        </div>
                        <hr>
                        
                        @include('layouts.flashmessage')
                        
                        @if(isset($error))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error!</strong> {{ $error }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <!-- Prices Table -->
                        @if($prices->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Location</th>
                                        <th>Category</th>
                                        <th>Item</th>
                                        <th>City</th>
                                        <th>Buy Price</th>
                                        <th>Sell Price</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($prices as $price)
                                        <tr>
                                            <td><strong>{{ $price['location'] ?? '-' }}</strong></td>
                                            <td>
                                                @if(!empty($price['category']))
                                                    <span class="badge bg-info">{{ $price['category'] }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>{{ $price['item'] ?? '-' }}</td>
                                            <td>{{ $price['city'] ?? '-' }}</td>
                                            <td>
                                                @if(!empty($price['buy_price']))
                                                    <span class="badge bg-success">{{ $price['buy_price'] }}</span>
                                                @elseif(!empty($price['lme_price']))
                                                    <span class="badge bg-primary">LME: {{ $price['lme_price'] }}</span>
                                                @elseif(!empty($price['pe_63']))
                                                    <span class="badge bg-warning">PE63: {{ $price['pe_63'] }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(!empty($price['sell_price']))
                                                    <span class="badge bg-danger">{{ $price['sell_price'] }}</span>
                                                @elseif(!empty($price['mcx_price']))
                                                    <span class="badge bg-primary">MCX: {{ $price['mcx_price'] }}</span>
                                                @elseif(!empty($price['drum_scrap']))
                                                    <span class="badge bg-warning">Drum: {{ $price['drum_scrap'] }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small class="text-muted">Live Data</small>
                                            </td>
                                        </tr>
                                        @if(!empty($price['injection_moulding']) || !empty($price['battery_price']) || !empty($price['black_cable']) || !empty($price['white_pipe']))
                                            <tr class="table-light">
                                                <td colspan="7">
                                                    <small class="text-muted">
                                                        <strong>Additional Details:</strong>
                                                        @if(!empty($price['injection_moulding'])) Injection Moulding: <span class="badge bg-secondary">{{ $price['injection_moulding'] }}</span> @endif
                                                        @if(!empty($price['battery_price'])) Battery: <span class="badge bg-secondary">{{ $price['battery_price'] }}</span> @endif
                                                        @if(!empty($price['black_cable'])) Black Cable: <span class="badge bg-secondary">{{ $price['black_cable'] }}</span> @endif
                                                        @if(!empty($price['white_pipe'])) White Pipe: <span class="badge bg-secondary">{{ $price['white_pipe'] }}</span> @endif
                                                        @if(!empty($price['grey_pvc'])) Grey PVC: <span class="badge bg-secondary">{{ $price['grey_pvc'] }}</span> @endif
                                                        @if(!empty($price['blow'])) Blow: <span class="badge bg-secondary">{{ $price['blow'] }}</span> @endif
                                                        @if(!empty($price['pe_100'])) PE100: <span class="badge bg-secondary">{{ $price['pe_100'] }}</span> @endif
                                                        @if(!empty($price['crate'])) Crate: <span class="badge bg-secondary">{{ $price['crate'] }}</span> @endif
                                                    </small>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="alert alert-info text-center">
                            <i class="fa fa-info-circle"></i> No price data available. Click "Refresh Prices" to load data.
                        </div>
                        @endif

                        <!-- Info Section -->
                        <div class="card mt-4 border-info">
                            <div class="card-body">
                                <h6 class="card-title text-info">
                                    <i class="fa fa-info-circle"></i> About Live Prices
                                </h6>
                                <p class="mb-2"><small>
                                    • <strong>No Database Required:</strong> Prices are scraped and cached in memory<br>
                                    • <strong>All Data Displayed:</strong> All {{ $totalCount ?? $prices->count() }} items shown on one page<br>
                                    • <strong>Auto-Load:</strong> Prices load automatically on first visit (cached for 1 hour)<br>
                                    • <strong>Refresh:</strong> Click "Refresh Prices" button to get latest data<br>
                                    • <strong>Fast Performance:</strong> Cached data loads instantly, refresh takes 2-5 minutes<br>
                                    • All prices are indicative and may vary based on quality and quantity<br>
                                    • <strong>Sources:</strong> 25+ URLs covering major cities across India
                                </small></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
