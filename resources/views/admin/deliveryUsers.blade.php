@extends('index')
@section('content')

<div class="content-body ">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        @include('layouts.flashmessage')
                        <h4 class="card-title">Delivery Users (Door Buyers) List</h4>
                        <hr>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <div class="d-flex justify-content-between align-items-center mb-3 px-3">
                                    <div class="d-flex align-items-center">
                                        <label class="me-2">Show</label>
                                        <select class="form-select form-select-sm" style="width: auto;" id="entriesPerPage" onchange="changeEntriesPerPage()">
                                            <option value="10" {{ $limit == 10 ? 'selected' : '' }}>10</option>
                                            <option value="20" {{ $limit == 20 ? 'selected' : '' }}>20</option>
                                            <option value="50" {{ $limit == 50 ? 'selected' : '' }}>50</option>
                                            <option value="100" {{ $limit == 100 ? 'selected' : '' }}>100</option>
                                        </select>
                                        <label class="ms-2">entries</label>
                                        <div class="ms-4">
                                            <label class="me-2">Filter:</label>
                                            <button type="button" 
                                                    class="btn btn-sm {{ request('app_version') == 'v1' ? 'btn-primary' : 'btn-outline-primary' }}" 
                                                    id="filterV1"
                                                    onclick="filterByVersion('v1')">
                                                V1
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm {{ request('app_version') == 'v2' ? 'btn-primary' : 'btn-outline-primary' }} ms-2" 
                                                    id="filterV2"
                                                    onclick="filterByVersion('v2')">
                                                V2
                                            </button>
                                            @if(request('app_version'))
                                                <button type="button" 
                                                        class="btn btn-sm btn-secondary ms-2" 
                                                        id="clearFilter"
                                                        onclick="filterByVersion('')">
                                                    Clear Filter
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                    <div>
                                        <label class="me-2">Search:</label>
                                        <input type="text" 
                                               class="form-control form-control-sm d-inline-block" 
                                               style="width: 200px;" 
                                               id="searchInput" 
                                               value="{{ request('search', '') }}"
                                               placeholder="Search by phone or name...">
                                        <button type="button" class="btn btn-sm btn-primary ms-2" id="searchButton">Search</button>
                                        <button type="button" class="btn btn-sm btn-secondary ms-2" id="clearButton" style="display: {{ request('search') ? 'inline-block' : 'none' }};">Clear</button>
                                    </div>
                                </div>
                                <table class="table table-striped table-hover" id="deliveryUsersTable" style="margin-bottom: 0;">
                                    <thead style="position: sticky; top: 0; z-index: 10;">
                                        <tr style="background-color: #6c5ce7; color: white;">
                                            <th style="min-width: 60px;">SL NO</th>
                                            <th style="min-width: 150px;">USER NAME</th>
                                            <th style="min-width: 180px;">EMAIL</th>
                                            <th style="min-width: 120px;">CONTACT NO</th>
                                            <th style="min-width: 350px; max-width: 450px;">ADDRESS</th>
                                            <th style="min-width: 120px;">SIGN UP DATE</th>
                                            <th style="min-width: 100px;">APP TYPE</th>
                                            <th style="min-width: 100px;">STATUS</th>
                                            <th style="min-width: 80px;">ACTION</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($users && $users->count() > 0)
                                            @foreach($users as $index => $user)
                                                <tr>
                                                    <td>{{ (($page - 1) * $limit) + $index + 1 }}</td>
                                                    <td>{{ $user->name ?? 'N/A' }}</td>
                                                    <td>{{ $user->email ?? 'N/A' }}</td>
                                                    <td>{{ $user->contact ?? ($user->delivery->contact ?? $user->delivery_boy->contact ?? $user->mob_num ?? 'N/A') }}</td>
                                                    <td>{{ $user->address ?? ($user->delivery->address ?? $user->delivery_boy->address ?? 'N/A') }}</td>
                                                    <td>{{ $user->created_at ? \Carbon\Carbon::parse($user->created_at)->format('Y-m-d') : 'N/A' }}</td>
                                                    <td>
                                                        @php
                                                            $appVersion = $user->app_version ?? 'v1';
                                                        @endphp
                                                        @if($appVersion === 'v2')
                                                            <span class="badge bg-primary">V2</span>
                                                        @else
                                                            <span class="badge bg-secondary">V1</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @php
                                                            $approvalStatus = $user->approval_status ?? ($user->delivery->approval_status ?? $user->delivery_boy->approval_status ?? 'pending');
                                                        @endphp
                                                        @if($approvalStatus === 'approved')
                                                            <span class="badge bg-success">Approved</span>
                                                        @elseif($approvalStatus === 'rejected')
                                                            <span class="badge bg-danger">Rejected</span>
                                                        @else
                                                            <span class="badge bg-warning">Pending</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('deliveryUserDocuments', ['userId' => $user->id]) }}" class="btn btn-sm btn-info" title="View Details">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="9" class="text-center">No matching records found</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            @if($totalPages > 1)
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div>
                                        Showing {{ (($page - 1) * $limit) + 1 }} to {{ min($page * $limit, $total) }} of {{ $total }} entries
                                    </div>
                                    <nav>
                                        <ul class="pagination mb-0">
                                            @php
                                                $paginationParams = ['limit' => $limit];
                                                if (request('search')) {
                                                    $paginationParams['search'] = request('search');
                                                }
                                                if (request('app_version')) {
                                                    $paginationParams['app_version'] = request('app_version');
                                                }
                                            @endphp
                                            <!-- Previous Page -->
                                            @if($page > 1)
                                                <li class="page-item">
                                                    <a class="page-link pagination-link" href="javascript:void(0);" data-page="{{ $page - 1 }}">Previous</a>
                                                </li>
                                            @else
                                                <li class="page-item disabled">
                                                    <span class="page-link">Previous</span>
                                                </li>
                                            @endif
                                            
                                            <!-- Page Numbers -->
                                            @for($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++)
                                                @if($i == $page)
                                                    <li class="page-item active">
                                                        <span class="page-link">{{ $i }}</span>
                                                    </li>
                                                @else
                                                    <li class="page-item">
                                                        <a class="page-link pagination-link" href="javascript:void(0);" data-page="{{ $i }}">{{ $i }}</a>
                                                    </li>
                                                @endif
                                            @endfor
                                            
                                            <!-- Next Page -->
                                            @if($hasMore)
                                                <li class="page-item">
                                                    <a class="page-link pagination-link" href="javascript:void(0);" data-page="{{ $page + 1 }}">Next</a>
                                                </li>
                                            @else
                                                <li class="page-item disabled">
                                                    <span class="page-link">Next</span>
                                                </li>
                                            @endif
                                        </ul>
                                    </nav>
                                </div>
                            @else
                                <div class="mt-3">
                                    Showing {{ $total }} to {{ $total }} of {{ $total }} entries
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('contentjs')
<style>
    #deliveryUsersTable td {
        vertical-align: top;
    }
    /* Add more space between user name and email */
    #deliveryUsersTable td:nth-child(2) {
        padding-right: 30px !important;
    }
    #deliveryUsersTable td:nth-child(3) {
        padding-left: 20px !important;
    }
    #deliveryUsersTable td:nth-child(5) {
        white-space: normal !important;
        word-wrap: break-word;
        word-break: break-word;
        max-width: 450px;
        line-height: 1.5;
        padding: 8px;
        max-height: calc(1.5em * 5); /* Allow 5 lines */
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 5;
        -webkit-box-orient: vertical;
    }
</style>
<script>
let currentPage = {{ $page ?? 1 }};
let currentLimit = {{ $limit ?? 10 }};
let currentSearch = '{{ request('search', '') }}';
let currentAppVersion = '{{ request('app_version', '') }}';

function loadDeliveryUsers(page, limit, search, appVersion) {
    // Show loading indicator
    const tbody = document.querySelector('#deliveryUsersTable tbody');
    tbody.innerHTML = '<tr><td colspan="9" class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';
    
    // Build URL
    let url = "{{ route('deliveryUsers') }}?page=" + page + "&limit=" + limit;
    if (search && search.trim()) {
        url += "&search=" + encodeURIComponent(search.trim());
    }
    
    // Update URL without reload
    window.history.pushState({}, '', url);
    
    // Fetch data via AJAX
    fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html'
        }
    })
    .then(response => response.text())
    .then(html => {
        // Create a temporary div to parse the HTML
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        
        // Extract table body
        const newTbody = tempDiv.querySelector('#deliveryUsersTable tbody');
        const newPagination = tempDiv.querySelector('.d-flex.justify-content-between.align-items-center.mt-3');
        const newPaginationInfo = tempDiv.querySelector('.mt-3');
        
        // Update table body
        if (newTbody) {
            tbody.innerHTML = newTbody.innerHTML;
        }
        
        // Update pagination
        const paginationContainer = document.querySelector('.d-flex.justify-content-between.align-items-center.mt-3');
        const paginationInfoContainer = document.querySelector('.mt-3');
        
        if (newPagination && paginationContainer) {
            paginationContainer.outerHTML = newPagination.outerHTML;
        } else if (newPaginationInfo && paginationInfoContainer) {
            paginationInfoContainer.outerHTML = newPaginationInfo.outerHTML;
        } else if (newPaginationInfo && !paginationInfoContainer) {
            // Add pagination info if it doesn't exist
            const tableContainer = document.querySelector('.table-responsive');
            if (tableContainer && tableContainer.nextElementSibling) {
                tableContainer.nextElementSibling.outerHTML = newPaginationInfo.outerHTML;
            }
        }
        
        // Update current state
        currentPage = page;
        currentLimit = limit;
        currentSearch = search || '';
        currentAppVersion = appVersion || '';
        
        // Update filter button states
        updateFilterButtons(appVersion);
        
        // Update clear button visibility
        const clearButton = document.getElementById('clearButton');
        if (clearButton) {
            clearButton.style.display = (search && search.trim()) ? 'inline-block' : 'none';
        }
        
        // Re-attach pagination event listeners
        attachPaginationListeners();
    })
    .catch(error => {
        console.error('Error loading data:', error);
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger">Error loading data. Please try again.</td></tr>';
    });
}

function attachPaginationListeners() {
    // Attach click handlers to pagination links
    document.querySelectorAll('.pagination a.pagination-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = parseInt(this.getAttribute('data-page')) || 1;
            loadDeliveryUsers(page, currentLimit, currentSearch, currentAppVersion);
        });
    });
}

function changeEntriesPerPage() {
    const limit = document.getElementById('entriesPerPage').value;
    loadDeliveryUsers(1, parseInt(limit), currentSearch, currentAppVersion);
}

function performSearch() {
    const search = document.getElementById('searchInput').value;
    loadDeliveryUsers(1, currentLimit, search, currentAppVersion);
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    loadDeliveryUsers(1, currentLimit, '', currentAppVersion);
}

function filterByVersion(version) {
    currentAppVersion = version || '';
    loadDeliveryUsers(1, currentLimit, currentSearch, currentAppVersion);
}

function updateFilterButtons(version) {
    const filterV1 = document.getElementById('filterV1');
    const filterV2 = document.getElementById('filterV2');
    const clearFilter = document.getElementById('clearFilter');
    
    if (filterV1) {
        if (version === 'v1') {
            filterV1.classList.remove('btn-outline-primary');
            filterV1.classList.add('btn-primary');
        } else {
            filterV1.classList.remove('btn-primary');
            filterV1.classList.add('btn-outline-primary');
        }
    }
    
    if (filterV2) {
        if (version === 'v2') {
            filterV2.classList.remove('btn-outline-primary');
            filterV2.classList.add('btn-primary');
        } else {
            filterV2.classList.remove('btn-primary');
            filterV2.classList.add('btn-outline-primary');
        }
    }
    
    // Show/hide clear filter button
    if (clearFilter) {
        if (version && version.trim()) {
            clearFilter.style.display = 'inline-block';
        } else {
            clearFilter.style.display = 'none';
        }
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Search button
    document.getElementById('searchButton').addEventListener('click', performSearch);
    
    // Clear button
    document.getElementById('clearButton').addEventListener('click', clearSearch);
    
    // Enter key in search input
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch();
        }
    });
    
    // Entries per page dropdown
    document.getElementById('entriesPerPage').addEventListener('change', changeEntriesPerPage);
    
    // Attach pagination listeners
    attachPaginationListeners();
});
</script>
@endsection











