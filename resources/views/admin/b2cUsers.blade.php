@extends('index')
@section('content')

<div class="content-body ">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        @include('layouts.flashmessage')
                        <h4 class="card-title">B2C Users List</h4>
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
                                <table class="table table-striped table-hover" id="b2cUsersTable" style="margin-bottom: 0;">
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
                                                    <td>{{ $user->contact ?? ($user->shop->contact ?? $user->mob_num ?? 'N/A') }}</td>
                                                    <td>{{ $user->address ?? ($user->shop->address ?? 'N/A') }}</td>
                                                    <td>{{ isset($user->created_at) && $user->created_at ? \Carbon\Carbon::parse($user->created_at)->format('Y-m-d') : 'N/A' }}</td>
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
                                                            // For v2 users, show approval_status; for v1 users, show del_status
                                                            $appVersion = $user->app_version ?? 'v1';
                                                            $approvalStatus = $user->approval_status ?? ($user->shop->approval_status ?? null);
                                                            
                                                            if ($appVersion === 'v2') {
                                                                // Always show approval status for v2 users (default to 'pending' if not set)
                                                                $status = $approvalStatus ?? 'pending';
                                                                if ($status === 'approved') {
                                                                    echo '<span class="badge bg-success">Approved</span>';
                                                                } elseif ($status === 'pending') {
                                                                    echo '<span class="badge bg-warning">Pending</span>';
                                                                } elseif ($status === 'rejected') {
                                                                    echo '<span class="badge bg-danger">Rejected</span>';
                                                                } else {
                                                                    echo '<span class="badge bg-warning">Pending</span>';
                                                                }
                                                            } else {
                                                                // Show del_status for v1 users
                                                                if (isset($user->del_status) && $user->del_status == 1) {
                                                                    echo '<span class="badge bg-success">Active</span>';
                                                                } else {
                                                                    echo '<span class="badge bg-danger">Inactive</span>';
                                                                }
                                                            }
                                                        @endphp
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('b2cUserDocuments', ['userId' => $user->id]) }}" class="btn btn-sm btn-info" title="View Details">
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
    #b2cUsersTable td {
        vertical-align: top;
    }
    /* Add more space between user name and email */
    #b2cUsersTable td:nth-child(2) {
        padding-right: 30px !important;
    }
    #b2cUsersTable td:nth-child(3) {
        padding-left: 20px !important;
    }
    #b2cUsersTable td:nth-child(5) {
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

function loadB2CUsers(page, limit, search) {
    // Show loading indicator
    const tbody = document.querySelector('#b2cUsersTable tbody');
    tbody.innerHTML = '<tr><td colspan="9" class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';
    
    // Build URL
    let url = "{{ route('b2cUsers') }}?page=" + page + "&limit=" + limit;
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
        const newTbody = tempDiv.querySelector('#b2cUsersTable tbody');
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
            loadB2CUsers(page, currentLimit, currentSearch);
        });
    });
}

function changeEntriesPerPage() {
    const limit = document.getElementById('entriesPerPage').value;
    loadB2CUsers(1, parseInt(limit), currentSearch);
}

function performSearch() {
    const search = document.getElementById('searchInput').value;
    loadB2CUsers(1, currentLimit, search);
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    loadB2CUsers(1, currentLimit, '');
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


