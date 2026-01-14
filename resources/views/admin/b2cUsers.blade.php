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
                                            <div class="ms-3 d-inline-block" id="approvalStatusFilter" style="display: {{ request('app_version') == 'v2' ? 'inline-block' : 'none' }};">
                                                <label class="me-2">Approval Status:</label>
                                                <button type="button" 
                                                        class="btn btn-sm {{ request('approval_status') == 'pending' ? 'btn-warning' : 'btn-outline-warning' }}" 
                                                        id="filterPending"
                                                        onclick="filterByApprovalStatus('pending')">
                                                    Pending
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm {{ request('approval_status') == 'approved' ? 'btn-success' : 'btn-outline-success' }} ms-2" 
                                                        id="filterApproved"
                                                        onclick="filterByApprovalStatus('approved')">
                                                    Approved
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm {{ request('approval_status') == 'rejected' ? 'btn-danger' : 'btn-outline-danger' }} ms-2" 
                                                        id="filterRejected"
                                                        onclick="filterByApprovalStatus('rejected')">
                                                    Rejected
                                                </button>
                                            </div>
                                            @if(request('app_version') || request('approval_status'))
                                                <button type="button" 
                                                        class="btn btn-sm btn-secondary ms-2" 
                                                        id="clearFilter"
                                                        onclick="clearAllFilters()">
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
                                        <a href="{{ route('b2cUsers.exportExcel') }}" 
                                           class="btn btn-sm btn-success ms-2" 
                                           id="downloadExcelBtn"
                                           title="Download all B2C users as Excel">
                                            <i class="fa fa-download"></i> Download All Excel
                                        </a>
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
                                            <th style="min-width: 120px;">CONTACTED</th>
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
                                                        @php
                                                            $isContacted = $user->is_contacted ?? false;
                                                        @endphp
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input contacted-toggle" 
                                                                   type="checkbox" 
                                                                   data-user-id="{{ $user->id }}"
                                                                   {{ $isContacted ? 'checked' : '' }}
                                                                   id="contacted_{{ $user->id }}"
                                                                   onchange="toggleContactedStatus({{ $user->id }}, this.checked)">
                                                            <label class="form-check-label" for="contacted_{{ $user->id }}">
                                                                <span class="badge {{ $isContacted ? 'bg-success' : 'bg-secondary' }}">
                                                                    {{ $isContacted ? 'Yes' : 'No' }}
                                                                </span>
                                                            </label>
                                                        </div>
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
                                                <td colspan="10" class="text-center">No matching records found</td>
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
                                                if (request('approval_status')) {
                                                    $paginationParams['approval_status'] = request('approval_status');
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
let currentAppVersion = '{{ request('app_version', '') }}';
let currentApprovalStatus = '{{ request('approval_status', '') }}';

function loadB2CUsers(page, limit, search, appVersion, approvalStatus) {
    // Show loading indicator
    const tbody = document.querySelector('#b2cUsersTable tbody');
    tbody.innerHTML = '<tr><td colspan="10" class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';
    
    // Build URL
    let url = "{{ route('b2cUsers') }}?page=" + page + "&limit=" + limit;
    if (search && search.trim()) {
        url += "&search=" + encodeURIComponent(search.trim());
    }
    if (appVersion && appVersion.trim()) {
        url += "&app_version=" + encodeURIComponent(appVersion.trim());
    }
    if (approvalStatus && approvalStatus.trim() && appVersion === 'v2') {
        url += "&approval_status=" + encodeURIComponent(approvalStatus.trim());
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
        currentAppVersion = appVersion || '';
        currentApprovalStatus = (approvalStatus && appVersion === 'v2') ? approvalStatus : '';
        
        // Update filter button states
        updateFilterButtons(appVersion, approvalStatus);
        
        // Update clear button visibility
        const clearButton = document.getElementById('clearButton');
        if (clearButton) {
            clearButton.style.display = (search && search.trim()) ? 'inline-block' : 'none';
        }
        
        // Re-attach pagination event listeners
        attachPaginationListeners();
        
        // Update download Excel button URL
        updateDownloadExcelUrl();
    })
    .catch(error => {
        console.error('Error loading data:', error);
        tbody.innerHTML = '<tr><td colspan="10" class="text-center text-danger">Error loading data. Please try again.</td></tr>';
    });
}

function toggleContactedStatus(userId, isContacted) {
    // Show loading state
    const checkbox = document.querySelector(`#contacted_${userId}`);
    const originalState = !isContacted; // Store original state in case of error
    
    // Disable checkbox during request
    checkbox.disabled = true;
    
    // Make API call to update contacted status
    fetch(`/b2cUsers/${userId}/contacted-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            is_contacted: isContacted
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Update the badge
            const label = checkbox.nextElementSibling;
            const badge = label.querySelector('.badge');
            if (isContacted) {
                badge.classList.remove('bg-secondary');
                badge.classList.add('bg-success');
                badge.textContent = 'Yes';
            } else {
                badge.classList.remove('bg-success');
                badge.classList.add('bg-secondary');
                badge.textContent = 'No';
            }
            console.log(`✅ Contacted status updated for user ${userId}: ${isContacted}`);
        } else {
            // Revert checkbox state on error
            checkbox.checked = originalState;
            alert('Failed to update contacted status: ' + (data.msg || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error updating contacted status:', error);
        // Revert checkbox state on error
        checkbox.checked = originalState;
        alert('Error updating contacted status. Please try again.');
    })
    .finally(() => {
        // Re-enable checkbox
        checkbox.disabled = false;
    });
}

function attachPaginationListeners() {
    // Attach click handlers to pagination links
    document.querySelectorAll('.pagination a.pagination-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = parseInt(this.getAttribute('data-page')) || 1;
            loadB2CUsers(page, currentLimit, currentSearch, currentAppVersion, currentApprovalStatus);
        });
    });
}

function changeEntriesPerPage() {
    const limit = document.getElementById('entriesPerPage').value;
    loadB2CUsers(1, parseInt(limit), currentSearch, currentAppVersion, currentApprovalStatus);
}

function performSearch() {
    const search = document.getElementById('searchInput').value;
    loadB2CUsers(1, currentLimit, search, currentAppVersion, currentApprovalStatus);
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    loadB2CUsers(1, currentLimit, '', currentAppVersion, currentApprovalStatus);
}

function filterByVersion(version) {
    currentAppVersion = version || '';
    // Clear approval_status filter if switching away from v2
    if (version !== 'v2') {
        currentApprovalStatus = '';
    }
    loadB2CUsers(1, currentLimit, currentSearch, currentAppVersion, currentApprovalStatus);
}

function filterByApprovalStatus(status) {
    // Only allow approval_status filter when v2 is selected
    if (currentAppVersion !== 'v2') {
        alert('Please select V2 filter first to use approval status filter');
        return;
    }
    currentApprovalStatus = status || '';
    loadB2CUsers(1, currentLimit, currentSearch, currentAppVersion, currentApprovalStatus);
}

function clearAllFilters() {
    currentAppVersion = '';
    currentApprovalStatus = '';
    loadB2CUsers(1, currentLimit, currentSearch, currentAppVersion, currentApprovalStatus);
}

function updateFilterButtons(version, approvalStatus) {
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
        if ((version && version.trim()) || (approvalStatus && approvalStatus.trim())) {
            clearFilter.style.display = 'inline-block';
        } else {
            clearFilter.style.display = 'none';
        }
    }
    
    // Update approval status filter buttons (only show when v2 is selected)
    const approvalStatusContainer = document.getElementById('approvalStatusFilter');
    if (approvalStatusContainer) {
        if (version === 'v2') {
            approvalStatusContainer.style.display = 'inline-block';
        } else {
            approvalStatusContainer.style.display = 'none';
        }
    }
    
    // Update approval status button states
    const filterPending = document.getElementById('filterPending');
    const filterApproved = document.getElementById('filterApproved');
    const filterRejected = document.getElementById('filterRejected');
    
    if (filterPending) {
        if (approvalStatus === 'pending') {
            filterPending.classList.remove('btn-outline-warning');
            filterPending.classList.add('btn-warning');
        } else {
            filterPending.classList.remove('btn-warning');
            filterPending.classList.add('btn-outline-warning');
        }
    }
    
    if (filterApproved) {
        if (approvalStatus === 'approved') {
            filterApproved.classList.remove('btn-outline-success');
            filterApproved.classList.add('btn-success');
        } else {
            filterApproved.classList.remove('btn-success');
            filterApproved.classList.add('btn-outline-success');
        }
    }
    
    if (filterRejected) {
        if (approvalStatus === 'rejected') {
            filterRejected.classList.remove('btn-outline-danger');
            filterRejected.classList.add('btn-danger');
        } else {
            filterRejected.classList.remove('btn-danger');
            filterRejected.classList.add('btn-outline-danger');
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
    
    // Initialize filter button states on page load
    updateFilterButtons(currentAppVersion, currentApprovalStatus);
    
    // Update download Excel button URL
    updateDownloadExcelUrl();
    
    // Attach pagination listeners
    attachPaginationListeners();
});

function updateDownloadExcelUrl() {
    const downloadBtn = document.getElementById('downloadExcelBtn');
    if (!downloadBtn) return;
    
    // Always export all B2C users regardless of filters
    let url = "{{ route('b2cUsers.exportExcel') }}";
    downloadBtn.href = url;
}
</script>
@endsection


