@extends('index')
@section('content')
<!--**********************************
    Content body start
***********************************-->

<div class="content-body">
    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header border-0">
                        <h4 class="card-title">V2 User Types Dashboard</h4>
                        <p class="text-muted mb-0">Statistics for New User (N), Recycler (R), Shop (S), Shop Recycler (SR), Delivery (D), and Customer (C) user types</p>
                    </div>
                    <div class="card-body pb-xl-4 pb-sm-3 pb-0">
                        <!-- User Type Counts -->
                        <div class="row mb-4" id="userTypeCounts">
                            <div class="col-xl-2 col-6">
                                <div class="content-box">
                                    <div class="icon-box icon-box-xl bg-secondary" style="margin-bottom: 15px;">
                                        <svg width="25" height="25" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 4L13.5 4.5L14.5 6L15 7.5L21 9ZM3 9L9 7.5L9.5 6L10.5 4.5L9 4L3 7V9ZM12 7.5C13.38 7.5 14.5 8.62 14.5 10C14.5 11.38 13.38 12.5 12 12.5C10.62 12.5 9.5 11.38 9.5 10C9.5 8.62 10.62 7.5 12 7.5ZM5.5 10C6.33 10 7 10.67 7 11.5C7 12.33 6.33 13 5.5 13C4.67 13 4 12.33 4 11.5C4 10.67 4.67 10 5.5 10ZM18.5 10C19.33 10 20 10.67 20 11.5C20 12.33 19.33 13 18.5 13C17.67 13 17 12.33 17 11.5C17 10.67 17.67 10 18.5 10ZM11 14L12 16L13 14H16L17.5 15.5L16.5 18.5L14 17.5L11 18.5L10 15.5L11 14ZM7 18C7 18 7.5 19.5 9 20.5C10.5 21.5 12 21 12 21L11 19L7 18Z" fill="white"/>
                                        </svg>
                                    </div>
                                    <div class="chart-num">
                                        <p>New User (N)</p>
                                        <h2 class="font-w700 mb-0" id="count-N">0</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-2 col-6">
                                <div class="content-box">
                                    <div class="icon-box icon-box-xl bg-primary" style="margin-bottom: 15px;">
                                        <svg width="25" height="25" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 2L2 7L12 12L22 7L12 2Z" fill="white"/>
                                            <path d="M2 17L12 22L22 17V12L12 17L2 12V17Z" fill="white"/>
                                        </svg>
                                    </div>
                                    <div class="chart-num">
                                        <p>Recycler (R)</p>
                                        <h2 class="font-w700 mb-0" id="count-R">0</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-2 col-6">
                                <div class="content-box">
                                    <div class="icon-box icon-box-xl bg-success" style="margin-bottom: 15px;">
                                        <svg width="25" height="25" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3Z" fill="white"/>
                                        </svg>
                                    </div>
                                    <div class="chart-num">
                                        <p>Shop (S)</p>
                                        <h2 class="font-w700 mb-0" id="count-S">0</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-2 col-6">
                                <div class="content-box">
                                    <div class="icon-box icon-box-xl bg-info" style="margin-bottom: 15px;">
                                        <svg width="25" height="25" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 2L2 7L12 12L22 7L12 2Z" fill="white"/>
                                            <path d="M2 17L12 22L22 17V12L12 17L2 12V17Z" fill="white"/>
                                            <path d="M2 12L12 17L22 12" stroke="white" stroke-width="2"/>
                                        </svg>
                                    </div>
                                    <div class="chart-num">
                                        <p>Shop Recycler (SR)</p>
                                        <h2 class="font-w700 mb-0" id="count-SR">0</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-2 col-6">
                                <div class="content-box">
                                    <div class="icon-box icon-box-xl bg-warning" style="margin-bottom: 15px;">
                                        <svg width="25" height="25" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 2C8.13 2 5 5.13 5 9C5 14.25 12 22 12 22C12 22 19 14.25 19 9C19 5.13 15.87 2 12 2ZM12 11.5C10.62 11.5 9.5 10.38 9.5 9C9.5 7.62 10.62 6.5 12 6.5C13.38 6.5 14.5 7.62 14.5 9C14.5 10.38 13.38 11.5 12 11.5Z" fill="white"/>
                                        </svg>
                                    </div>
                                    <div class="chart-num">
                                        <p>Delivery (D)</p>
                                        <h2 class="font-w700 mb-0" id="count-D">0</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-2 col-6">
                                <div class="content-box">
                                    <div class="icon-box icon-box-xl bg-danger" style="margin-bottom: 15px;">
                                        <svg width="25" height="25" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z" fill="white"/>
                                            <path d="M3 22C3 18.6863 5.68629 16 9 16H15C18.3137 16 21 18.6863 21 22H3Z" fill="white"/>
                                        </svg>
                                    </div>
                                    <div class="chart-num">
                                        <p>Customer (C)</p>
                                        <h2 class="font-w700 mb-0" id="count-C">0</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-2 col-6">
                                <div class="content-box">
                                    <div class="icon-box icon-box-xl bg-dark" style="margin-bottom: 15px;">
                                        <svg width="25" height="25" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" fill="white"/>
                                            <path d="M12 14C16.4183 14 20 15.7909 20 18V20H4V18C4 15.7909 7.58172 14 12 14Z" fill="white"/>
                                        </svg>
                                    </div>
                                    <div class="chart-num">
                                        <p>Total Users</p>
                                        <h2 class="font-w700 mb-0" id="totalUsers">0</h2>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Orders Count -->
                        <div class="row mb-4">
                            <div class="col-xl-12">
                                <div class="card">
                                    <div class="card-header border-0">
                                        <h4 class="card-title">Orders Overview</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-xl-3 col-6">
                                                <div class="content-box text-center">
                                                    <h3 class="font-w700 mb-0" id="customerAppOrders">0</h3>
                                                    <p class="text-muted mb-0">Customer App Orders (v2)</p>
                                                </div>
                                            </div>
                                            <div class="col-xl-3 col-6">
                                                <div class="content-box text-center">
                                                    <h3 class="font-w700 mb-0" id="bulkOrders">0</h3>
                                                    <p class="text-muted mb-0">Bulk Orders</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Monthly Downloads Chart -->
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="card">
                                    <div class="card-header border-0">
                                        <h4 class="card-title">Monthly User Registrations (Downloads)</h4>
                                    </div>
                                    <div class="card-body">
                                        <div id="monthlyDownloadsChart" style="min-height: 400px;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Customer App Orders -->
                        <div class="row mt-4">
                            <div class="col-xl-12">
                                <div class="card">
                                    <div class="card-header border-0">
                                        <h4 class="card-title">Recent Customer App Orders (v2)</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered" id="customerAppOrdersTable">
                                                <thead>
                                                    <tr>
                                                        <th>Sl.No</th>
                                                        <th>Order ID</th>
                                                        <th>Order Number</th>
                                                        <th>Customer ID</th>
                                                        <th>Shop ID</th>
                                                        <th>Status</th>
                                                        <th>Amount</th>
                                                        <th>Date</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bulk Orders -->
                        <div class="row mt-4">
                            <div class="col-xl-12">
                                <div class="card">
                                    <div class="card-header border-0">
                                        <h4 class="card-title">Recent Bulk Orders</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped" id="bulkOrdersTable">
                                                <thead>
                                                    <tr>
                                                        <th>Order ID</th>
                                                        <th>Order Number</th>
                                                        <th>Bulk Request ID</th>
                                                        <th>Customer ID</th>
                                                        <th>Shop ID</th>
                                                        <th>Status</th>
                                                        <th>Amount</th>
                                                        <th>Date</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td colspan="8" class="text-center">Loading orders...</td>
                                                    </tr>
                                                </tbody>
                                            </table>
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
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" role="dialog" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderDetailsModalLabel">Order Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable for customer app orders
    initializeCustomerAppOrdersTable();
    
    // Load dashboard data (for counts and charts)
    loadV2DashboardData();
    
    // Refresh dashboard data every 5 minutes (but not the DataTable - it handles its own refresh)
    setInterval(loadV2DashboardData, 300000);
});

function loadV2DashboardData() {
    fetch('/api/dashboard/v2-user-types')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data) {
                updateDashboard(data.data);
            } else {
                console.error('Error loading dashboard data:', data.msg);
            }
        })
        .catch(error => {
            console.error('Error fetching dashboard data:', error);
        });
}

function updateDashboard(data) {
    // Update user type counts
    if (data.userTypes) {
        Object.keys(data.userTypes).forEach(type => {
            const countElement = document.getElementById(`count-${type}`);
            if (countElement) {
                countElement.textContent = data.userTypes[type].count || 0;
            }
        });
    }
    
    // Update total users
    const totalUsersElement = document.getElementById('totalUsers');
    if (totalUsersElement) {
        totalUsersElement.textContent = data.totalUsers || 0;
    }
    
    // Update order counts
    const customerAppOrdersElement = document.getElementById('customerAppOrders');
    if (customerAppOrdersElement) {
        customerAppOrdersElement.textContent = data.orders?.customerAppOrders || 0;
    }
    
    const bulkOrdersElement = document.getElementById('bulkOrders');
    if (bulkOrdersElement) {
        bulkOrdersElement.textContent = data.orders?.bulkOrders || 0;
    }
    
    // Update bulk orders table (customer app orders now uses DataTables with pagination)
    updateBulkOrdersTable(data.orders?.recentBulkOrders || []);
    
    // Update monthly downloads chart
    updateMonthlyDownloadsChart(data.userTypes);
}

let monthlyChart = null;

function updateMonthlyDownloadsChart(userTypes) {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    
    const series = [];
    const colors = {
        'N': '#6c757d',
        'R': '#007bff',
        'S': '#28a745',
        'SR': '#17a2b8',
        'D': '#ffc107',
        'C': '#dc3545'
    };
    
    Object.keys(userTypes).forEach(type => {
        const monthlyCounts = userTypes[type].monthlyCounts || [];
        series.push({
            name: userTypes[type].name,
            data: monthlyCounts,
            color: colors[type] || '#6c757d'
        });
    });
    
    const options = {
        series: series,
        chart: {
            type: 'line',
            height: 400,
            toolbar: {
                show: true
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 2
        },
        xaxis: {
            categories: months
        },
        yaxis: {
            title: {
                text: 'Number of Users'
            }
        },
        title: {
            text: 'Monthly User Registrations by Type',
            align: 'left'
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right'
        },
        tooltip: {
            shared: true,
            intersect: false
        }
    };
    
    // Destroy existing chart if it exists
    if (monthlyChart) {
        monthlyChart.destroy();
    }
    
    monthlyChart = new ApexCharts(document.querySelector("#monthlyDownloadsChart"), options);
    monthlyChart.render();
}

// Initialize DataTable for customer app orders
let customerAppOrdersTable = null;

function initializeCustomerAppOrdersTable() {
    if (customerAppOrdersTable) {
        customerAppOrdersTable.destroy();
    }
    
    customerAppOrdersTable = $('#customerAppOrdersTable').DataTable({
        processing: true,
        serverSide: true,
        destroy: true,
        ajax: {
            url: "{{ route('api.dashboard.customerAppOrders') }}",
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            error: function(xhr, error, thrown) {
                console.error('DataTables Ajax Error:', {
                    xhr: xhr,
                    error: error,
                    thrown: thrown,
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    responseJSON: xhr.responseJSON
                });
                
                let errorMsg = 'Error loading orders. ';
                if (xhr.status === 0) {
                    errorMsg += 'Network error - please check your connection.';
                } else if (xhr.status === 404) {
                    errorMsg += 'Endpoint not found.';
                } else if (xhr.status === 500) {
                    errorMsg += 'Server error.';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMsg += ' ' + xhr.responseJSON.error;
                    }
                } else {
                    errorMsg += 'Status: ' + xhr.status + ' ' + xhr.statusText;
                }
                
                alert(errorMsg);
            }
        },
        columns: [
            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            {
                data: 'id',
                name: 'id'
            },
            {
                data: 'order_number',
                name: 'order_number'
            },
            {
                data: 'customer_id',
                name: 'customer_id'
            },
            {
                data: 'shop_id',
                name: 'shop_id'
            },
            {
                data: 'status_badge',
                name: 'status',
                orderable: true,
                searchable: false
            },
            {
                data: 'amount',
                name: 'amount',
                orderable: true,
                searchable: false
            },
            {
                data: 'date',
                name: 'date',
                orderable: true
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            }
        ],
        order: [[1, 'desc']], // Sort by Order ID descending
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>'
        }
    });
}

function updateCustomerAppOrdersTable(orders) {
    // This function is kept for backward compatibility but won't be used
    // DataTables will handle the table updates via AJAX
    console.log('updateCustomerAppOrdersTable called - DataTables handles this automatically');
}

function updateBulkOrdersTable(orders) {
    const tbody = document.querySelector('#bulkOrdersTable tbody');
    if (!orders || orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">No bulk orders found</td></tr>';
        return;
    }
    
    tbody.innerHTML = orders.map(order => {
        const orderDate = order.created_at || order.date ? new Date(order.created_at || order.date).toLocaleDateString() : 'N/A';
        const amount = order.total_amount || order.estim_price || order.amount || '0.00';
        const status = getStatusLabel(order.status);
        const orderId = order.id || 'N/A';
        return `
            <tr>
                <td>${orderId}</td>
                <td>${order.order_no || order.order_number || 'N/A'}</td>
                <td>${order.bulk_request_id || 'N/A'}</td>
                <td>${order.customer_id || 'N/A'}</td>
                <td>${order.shop_id || 'N/A'}</td>
                <td><span class="badge badge-${getStatusColor(order.status)}">${status}</span></td>
                <td>₹${parseFloat(amount).toFixed(2)}</td>
                <td>${orderDate}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="viewOrderDetails(${orderId}, 'bulk')">
                        <i class="fa fa-eye"></i> View Details
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function getStatusLabel(status) {
    const statusMap = {
        1: 'Scheduled',
        2: 'Accepted',
        3: 'In Progress',
        4: 'Picked Up',
        5: 'Completed',
        6: 'Accepted by Other',
        7: 'Cancelled'
    };
    return statusMap[status] || status || 'N/A';
}

function getStatusColor(status) {
    if (status === 5) {
        return 'success';
    } else if (status === 1 || status === 2 || status === 3) {
        return 'warning';
    } else if (status === 7) {
        return 'danger';
    } else if (status === 6) {
        return 'info';
    }
    return 'secondary';
}

function viewOrderDetails(orderId, orderType) {
    // Show modal
    $('#orderDetailsModal').modal('show');
    
    // Set loading state
    document.getElementById('orderDetailsContent').innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-2">Loading order details...</p>
        </div>
    `;
    
    // Fetch order details from Node.js API via PHP controller
    fetch(`/api/dashboard/order/${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data) {
                displayOrderDetails(data.data, orderType);
            } else {
                showError(data.msg || 'Failed to load order details');
            }
        })
        .catch(error => {
            console.error('Error fetching order details:', error);
            showError('Failed to load order details. Please try again.');
        });
}

function showError(message) {
    document.getElementById('orderDetailsContent').innerHTML = `
        <div class="alert alert-danger">
            <h5>Error</h5>
            <p>${message}</p>
        </div>
    `;
}

function displayOrderDetails(order, orderType) {
    const orderDate = order.created_at || order.date ? new Date(order.created_at || order.date).toLocaleDateString() : 'N/A';
    const amount = order.total_amount || order.estim_price || order.amount || '0.00';
    const status = getStatusLabel(order.status);
    const statusColor = getStatusColor(order.status);
    
    // Parse customer details
    let customerName = 'N/A';
    let customerAddress = 'N/A';
    let customerPhone = 'N/A';
    
    if (order.customerdetails) {
        try {
            const customerDetails = typeof order.customerdetails === 'string' 
                ? JSON.parse(order.customerdetails) 
                : order.customerdetails;
            customerName = customerDetails.name || customerDetails.customer_name || customerDetails.full_name || 'N/A';
            customerAddress = customerDetails.address || customerDetails.customerdetails || customerDetails.full_address || 'N/A';
            customerPhone = customerDetails.phone || customerDetails.mobile || customerDetails.contact || customerDetails.mob_num || customerDetails.phone_number || 'N/A';
        } catch (e) {
            // If parsing fails, try to extract as string
            if (typeof order.customerdetails === 'string') {
                customerAddress = order.customerdetails;
            }
        }
    }
    
    // Also check direct order fields for customer info
    if (customerName === 'N/A' && order.customer_name) {
        customerName = order.customer_name;
    }
    if (customerPhone === 'N/A' && order.customer_phone) {
        customerPhone = order.customer_phone;
    }
    if (customerAddress === 'N/A' && order.customer_address) {
        customerAddress = order.customer_address;
    }
    
    // Parse shop details
    let shopName = 'N/A';
    if (order.shopdetails) {
        try {
            const shopDetails = typeof order.shopdetails === 'string' 
                ? JSON.parse(order.shopdetails) 
                : order.shopdetails;
            shopName = shopDetails.shopname || shopDetails.shop_name || shopDetails.name || 'N/A';
        } catch (e) {
            shopName = order.shopdetails;
        }
    }
    
    // Parse order details
    let orderItems = [];
    if (order.orderdetails) {
        try {
            const orderDetails = typeof order.orderdetails === 'string' 
                ? JSON.parse(order.orderdetails) 
                : order.orderdetails;
            if (Array.isArray(orderDetails)) {
                orderItems = orderDetails;
            } else if (orderDetails.orders) {
                // Handle nested structure: { orders: { category: [items] } }
                Object.entries(orderDetails.orders).forEach(([category, subcats]) => {
                    if (Array.isArray(subcats)) {
                        orderItems.push(...subcats);
                    } else if (subcats && typeof subcats === 'object') {
                        // Handle nested categories
                        Object.values(subcats).forEach(items => {
                            if (Array.isArray(items)) {
                                orderItems.push(...items);
                            }
                        });
                    }
                });
            } else if (typeof orderDetails === 'object') {
                // Try to extract items from object directly
                Object.values(orderDetails).forEach(value => {
                    if (Array.isArray(value)) {
                        orderItems.push(...value);
                    }
                });
            }
        } catch (e) {
            console.error('Error parsing order details:', e);
        }
    }
    
    // Get images
    const images = [];
    for (let i = 1; i <= 6; i++) {
        if (order[`image${i}`]) {
            images.push(order[`image${i}`]);
        }
    }
    
    const html = `
        <div class="order-details">
            <h5 class="mb-3">Order Information</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th width="30%">Order ID:</th>
                            <td>${order.id || 'N/A'}</td>
                        </tr>
                        <tr>
                            <th>Order Number:</th>
                            <td>${order.order_no || order.order_number || 'N/A'}</td>
                        </tr>
                        ${orderType === 'bulk' ? `
                        <tr>
                            <th>Bulk Request ID:</th>
                            <td>${order.bulk_request_id || 'N/A'}</td>
                        </tr>
                        ` : ''}
                        <tr>
                            <th>Status:</th>
                            <td><span class="badge badge-${statusColor}">${status}</span></td>
                        </tr>
                        <tr>
                            <th>Date:</th>
                            <td>${orderDate}</td>
                        </tr>
                        <tr>
                            <th>Estimated Weight:</th>
                            <td>${order.estim_weight || order.estimated_weight || 'N/A'} kg</td>
                        </tr>
                        <tr>
                            <th>Estimated Price:</th>
                            <td>₹${parseFloat(order.estim_price || order.estimated_price || 0).toFixed(2)}</td>
                        </tr>
                        <tr>
                            <th>Total Amount:</th>
                            <td><strong>₹${parseFloat(amount).toFixed(2)}</strong></td>
                        </tr>
                        <tr>
                            <th>Delivery Type:</th>
                            <td>${order.del_type || order.delivery_type || 'N/A'}</td>
                        </tr>
                        ${order.preferred_pickup_time ? `
                        <tr>
                            <th>Preferred Pickup Time:</th>
                            <td>${order.preferred_pickup_time}</td>
                        </tr>
                        ` : ''}
                    </tbody>
                </table>
            </div>
            
            <h5 class="mb-3 mt-4">Customer Information</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th width="30%">Customer ID:</th>
                            <td>${order.customer_id || 'N/A'}</td>
                        </tr>
                        <tr>
                            <th>Name:</th>
                            <td>${customerName}</td>
                        </tr>
                        <tr>
                            <th>Address:</th>
                            <td>${customerAddress}</td>
                        </tr>
                        <tr>
                            <th>Phone:</th>
                            <td>${customerPhone}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            ${order.accepted_vendor ? `
            <h5 class="mb-3 mt-4">Accepted Vendor Information</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tbody>
                        ${order.accepted_vendor.type === 'shop' ? `
                        <tr>
                            <th width="30%">Accepted By:</th>
                            <td><span class="badge badge-primary">Shop</span></td>
                        </tr>
                        <tr>
                            <th>Shop ID:</th>
                            <td>${order.accepted_vendor.shop_id || 'N/A'}</td>
                        </tr>
                        <tr>
                            <th>Shop Name:</th>
                            <td>${order.accepted_vendor.shop_name || shopName || 'N/A'}${order.accepted_vendor.shop_not_found ? ' <span class="badge badge-warning">Shop not found in database</span>' : ''}</td>
                        </tr>
                        <tr>
                            <th>Vendor User ID:</th>
                            <td>${order.accepted_vendor.user_id || 'N/A'}</td>
                        </tr>
                        <tr>
                            <th>Vendor Name:</th>
                            <td>${order.accepted_vendor.user_name || 'N/A'}</td>
                        </tr>
                        <tr>
                            <th>Vendor Mobile:</th>
                            <td>${order.accepted_vendor.user_mobile || order.accepted_vendor.shop_contact || 'N/A'}</td>
                        </tr>
                        <tr>
                            <th>Vendor Email:</th>
                            <td>${order.accepted_vendor.user_email || 'N/A'}</td>
                        </tr>
                        <tr>
                            <th>User Type:</th>
                            <td><span class="badge badge-info">${order.accepted_vendor.user_type || 'N/A'}</span></td>
                        </tr>
                        <tr>
                            <th>App Version:</th>
                            <td>${order.accepted_vendor.app_version || 'N/A'}</td>
                        </tr>
                        ${order.accepted_vendor.shop_address ? `
                        <tr>
                            <th>Shop Address:</th>
                            <td>${order.accepted_vendor.shop_address}${order.accepted_vendor.shop_place ? ', ' + order.accepted_vendor.shop_place : ''}${order.accepted_vendor.shop_state ? ', ' + order.accepted_vendor.shop_state : ''}${order.accepted_vendor.shop_pincode ? ' - ' + order.accepted_vendor.shop_pincode : ''}</td>
                        </tr>
                        ` : ''}
                        ` : `
                        <tr>
                            <th width="30%">Accepted By:</th>
                            <td><span class="badge badge-info">Delivery Boy</span></td>
                        </tr>
                        <tr>
                            <th>Delivery Boy ID:</th>
                            <td>${order.accepted_vendor.user_id || order.delv_id || order.delv_boy_id || 'N/A'}</td>
                        </tr>
                        <tr>
                            <th>Name:</th>
                            <td>${order.accepted_vendor.user_name || 'N/A'}</td>
                        </tr>
                        <tr>
                            <th>Mobile:</th>
                            <td>${order.accepted_vendor.user_mobile || 'N/A'}</td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td>${order.accepted_vendor.user_email || 'N/A'}</td>
                        </tr>
                        <tr>
                            <th>User Type:</th>
                            <td><span class="badge badge-info">${order.accepted_vendor.user_type || 'N/A'}</span></td>
                        </tr>
                        <tr>
                            <th>App Version:</th>
                            <td>${order.accepted_vendor.app_version || 'N/A'}</td>
                        </tr>
                        `}
                    </tbody>
                </table>
            </div>
            ` : order.shop_id ? `
            <h5 class="mb-3 mt-4">Shop Information</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th width="30%">Shop ID:</th>
                            <td>${order.shop_id || 'N/A'}</td>
                        </tr>
                        <tr>
                            <th>Shop Name:</th>
                            <td>${shopName}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            ` : ''}
            
            ${orderItems.length > 0 ? `
            <h5 class="mb-3 mt-4">Order Items</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Weight</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${orderItems.map(item => {
                            // Get item name - check multiple field names (material_name is used in v2 orders)
                            const itemName = item.material_name || item.name || item.category_name || item.item_name || item.subcategory_name || 'N/A';
                            
                            // Get weight - check multiple field names (expected_weight_kg is used in v2 orders)
                            const weight = item.expected_weight_kg || item.expected_weight || item.approximate_weight || item.weight || item.approximateWeight || item.estimated_weight || item.actual_weight_kg || item.actual_weight || 'N/A';
                            const weightValue = weight !== 'N/A' ? (typeof weight === 'number' ? weight : parseFloat(weight)) : null;
                            
                            // Get price per kg (price_per_kg is used in v2 orders)
                            const amountPerKg = item.price_per_kg || item.pricePerKg || item.amount_per_kg || item.amountPerKg || item.rate || item.price_unit || 0;
                            const amountPerKgValue = typeof amountPerKg === 'number' ? amountPerKg : parseFloat(amountPerKg || 0);
                            
                            // Calculate total price: weight * price_per_kg
                            let totalPrice = 0;
                            if (weightValue !== null && !isNaN(weightValue) && amountPerKgValue > 0) {
                                totalPrice = weightValue * amountPerKgValue;
                            } else {
                                // Fallback to direct price field if calculation not possible
                                totalPrice = parseFloat(item.price || item.total_price || item.amount || 0);
                            }
                            
                            // Format weight display
                            const weightDisplay = weightValue !== null && !isNaN(weightValue) 
                                ? `${weightValue} kg` 
                                : (weight || 'N/A');
                            
                            return `
                            <tr>
                                <td>${itemName}</td>
                                <td>${item.quantity || item.qty || '-'}</td>
                                <td>${weightDisplay}</td>
                                <td>₹${totalPrice.toFixed(2)}</td>
                            </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
            ` : ''}
            
            ${images.length > 0 ? `
            <h5 class="mb-3 mt-4">Order Images</h5>
            <div class="row">
                ${images.map((img, idx) => `
                    <div class="col-md-4 mb-3">
                        <img src="${img}" alt="Order Image ${idx + 1}" class="img-fluid img-thumbnail" style="max-height: 200px; width: 100%; object-fit: cover;">
                    </div>
                `).join('')}
            </div>
            ` : ''}
            
            ${order.monthly_subscribed_vendors && order.monthly_subscribed_vendors.length > 0 ? `
            <h5 class="mb-3 mt-4">Monthly Subscribed Vendors (${order.monthly_subscribed_vendors.length})</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Vendor ID</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>Email</th>
                            <th>User Type</th>
                            <th>Shop Name</th>
                            <th>Subscription Ends</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${order.monthly_subscribed_vendors.map(vendor => {
                            const isAlreadyNotified = order.notified_vendors && order.notified_vendors.some(nv => parseInt(nv.id) === parseInt(vendor.id));
                            const subscriptionEnds = vendor.subscription_ends_at ? new Date(vendor.subscription_ends_at).toLocaleDateString() : 'N/A';
                            return `
                            <tr>
                                <td>${vendor.id || 'N/A'}</td>
                                <td>${vendor.name || 'N/A'}</td>
                                <td>${vendor.mobile || 'N/A'}</td>
                                <td>${vendor.email || 'N/A'}</td>
                                <td><span class="badge badge-secondary">${vendor.user_type || 'N/A'}</span></td>
                                <td>${vendor.shop_name || 'N/A'}</td>
                                <td>${subscriptionEnds}</td>
                                <td>
                                    ${isAlreadyNotified ? `
                                        <span class="badge badge-success">Already Notified</span>
                                    ` : `
                                        <button class="btn btn-sm btn-primary" onclick="addVendorToOrder(event, ${order.id}, ${vendor.id}, '${vendor.name || 'N/A'}')" title="Add to Notified Vendors">
                                            <i class="fa fa-plus"></i> Add
                                        </button>
                                    `}
                                </td>
                            </tr>
                        `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
            ` : ''}
            
            ${order.notified_vendors && order.notified_vendors.length > 0 ? `
            <h5 class="mb-3 mt-4">Notified Vendors (${order.notified_vendors.length}) <small class="text-muted">- Sorted by distance</small></h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Vendor ID</th>
                            <th>Name</th>
                            <th>Shop Name</th>
                            <th>Mobile</th>
                            <th>Distance</th>
                            <th>User Type</th>
                            <th>App Version</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${order.notified_vendors.map((vendor, index) => `
                            <tr class="${index < 5 ? 'table-success' : ''}">
                                <td>${vendor.id || 'N/A'}</td>
                                <td>${vendor.name || 'N/A'}</td>
                                <td>${vendor.shop_name || 'N/A'}</td>
                                <td>${vendor.mobile || 'N/A'}</td>
                                <td>
                                    ${vendor.distance_km !== null && vendor.distance_km !== undefined 
                                        ? `<span class="badge badge-${vendor.distance_km <= 5 ? 'success' : vendor.distance_km <= 10 ? 'warning' : 'secondary'}">${vendor.distance_km} km</span>` 
                                        : '<span class="badge badge-light">N/A</span>'}
                                </td>
                                <td><span class="badge badge-secondary">${vendor.user_type || 'N/A'}</span></td>
                                <td><span class="badge badge-${vendor.app_version === 'v2' ? 'primary' : 'secondary'}">${vendor.app_version || 'N/A'}</span></td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            ` : `
            <div class="alert alert-info mt-4">
                <i class="fa fa-info-circle"></i> No vendors have been notified about this order yet.
            </div>
            `}
            
            ${(() => {
                // Get bulk notified vendors from global variable (from SMS response) or order data
                const bulkVendorsFromResponse = window.bulkNotifiedVendors && window.bulkNotifiedVendors[order.id] 
                    ? window.bulkNotifiedVendors[order.id] 
                    : [];
                
                // Get bulk notified vendors from order (phone numbers stored in database)
                const bulkVendorsFromOrder = order.bulk_notified_vendors || [];
                
                // Use response data if available (has full details), otherwise use order data (phone numbers)
                const bulkVendors = bulkVendorsFromResponse.length > 0 ? bulkVendorsFromResponse : bulkVendorsFromOrder;
                
                if (bulkVendors && bulkVendors.length > 0) {
                    return `
                    <div class="card mt-4" style="border-left: 4px solid #ffc107;">
                        <div class="card-header bg-warning bg-opacity-10">
                            <h5 class="mb-0">
                                <i class="fa fa-envelope"></i> Bulk Notified Vendors (${bulkVendors.length})
                            </h5>
                            <small class="text-muted">Vendors notified via bulk SMS from bulk_message_notifications</small>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Phone Number</th>
                                            <th>Vendor Name</th>
                                            <th>City</th>
                                            <th>Street</th>
                                            <th>SMS Status</th>
                                            <th>Message ID</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${bulkVendors.map((vendor, idx) => {
                                            // Handle both string (phone number) and object format
                                            const vendorData = typeof vendor === 'string' ? { phone_number: vendor } : vendor;
                                            const phone = vendorData.phone_number || vendorData.phone || vendor || 'N/A';
                                            const name = vendorData.vendor_name || vendorData.title || vendorData.name || 'N/A';
                                            const city = vendorData.city || 'N/A';
                                            const street = vendorData.street || 'N/A';
                                            const status = vendorData.success !== undefined 
                                                ? (vendorData.success 
                                                    ? '<span class="badge badge-success">✅ Sent</span>' 
                                                    : '<span class="badge badge-danger">❌ Failed</span>')
                                                : '<span class="badge badge-info">Notified</span>';
                                            const messageId = vendorData.message_id || (vendorData.sms_response && Array.isArray(vendorData.sms_response) && vendorData.sms_response[0] && vendorData.sms_response[0].msgid) || 'N/A';
                                            
                                            return `
                                            <tr>
                                                <td>${phone}</td>
                                                <td>${name}</td>
                                                <td>${city}</td>
                                                <td>${street}</td>
                                                <td>${status}</td>
                                                <td><small>${messageId}</small></td>
                                            </tr>
                                            `;
                                        }).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    `;
                }
                return '';
            })()}
            
            <!-- Order Status Management -->
            <div class="card mt-4 border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fa fa-cog"></i> Order Status Management</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <label class="form-label"><strong>Current Status:</strong></label>
                            <div id="currentStatusDisplay">
                                <span class="badge badge-${statusColor} badge-lg" style="font-size: 1rem; padding: 8px 16px;">${status}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Update Status:</strong></label>
                            <div class="input-group">
                                <select id="orderStatusSelect" class="form-control">
                                    <option value="1" ${order.status == 1 ? 'selected' : ''}>1 - Scheduled</option>
                                    <option value="2" ${order.status == 2 ? 'selected' : ''}>2 - Accepted</option>
                                    <option value="3" ${order.status == 3 ? 'selected' : ''}>3 - In Progress</option>
                                    <option value="4" ${order.status == 4 ? 'selected' : ''}>4 - Picked Up</option>
                                    <option value="5" ${order.status == 5 ? 'selected' : ''}>5 - Completed</option>
                                    <option value="6" ${order.status == 6 ? 'selected' : ''}>6 - Accepted by Other</option>
                                    <option value="7" ${order.status == 7 ? 'selected' : ''}>7 - Cancelled</option>
                                </select>
                                <div class="input-group-append">
                                    <button class="btn btn-primary" onclick="updateOrderStatus(${order.id})">
                                        <i class="fa fa-save"></i> Update
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label"><strong>Admin Notes (optional):</strong></label>
                        <textarea id="orderStatusNotes" class="form-control" rows="2" placeholder="Add notes about this status change..."></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Vendor Assignment -->
            <div class="card mt-4 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fa fa-user-plus"></i> Vendor Assignment</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label"><strong>Search Vendor:</strong></label>
                            <div class="input-group">
                                <input type="text" id="vendorSearchInput" class="form-control" placeholder="Enter name, mobile, or shop name...">
                                <div class="input-group-append">
                                    <button class="btn btn-success" onclick="searchVendors()">
                                        <i class="fa fa-search"></i> Search
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">Min 2 characters required</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Filter by Type:</strong></label>
                            <select id="vendorTypeFilter" class="form-control">
                                <option value="">All Types</option>
                                <option value="R">Recycler (R)</option>
                                <option value="S">Shop (S)</option>
                                <option value="SR">Shop + Recycler (SR)</option>
                                <option value="D">Delivery (D)</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Vendor Search Results -->
                    <div id="vendorSearchResults" class="mt-3" style="display: none;">
                        <h6>Search Results:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Mobile</th>
                                        <th>Type</th>
                                        <th>Shop Name</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="vendorSearchResultsBody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Nearby Vendors -->
                    <div class="row">
                        <div class="col-md-8">
                            <label class="form-label"><strong>Find Nearby Vendors (by order location):</strong></label>
                        </div>
                        <div class="col-md-4">
                            <select id="nearbyRadius" class="form-control form-control-sm">
                                <option value="5">Within 5 km</option>
                                <option value="10">Within 10 km</option>
                                <option value="20" selected>Within 20 km</option>
                                <option value="50">Within 50 km</option>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-info btn-sm mt-2" onclick="getAvailableVendorsForOrder(${order.id})">
                        <i class="fa fa-map-marker"></i> Find Nearby Vendors
                    </button>
                    
                    <!-- Nearby Vendors Results -->
                    <div id="nearbyVendorsResults" class="mt-3" style="display: none;">
                        <h6>Available Vendors Nearby:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Shop Name</th>
                                        <th>Distance</th>
                                        <th>Type</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="nearbyVendorsResultsBody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <div class="btn-group" role="group">
                    <button class="btn btn-primary" onclick="addNearbyNUsersToOrder(event, ${order.id})">
                        <i class="fa fa-plus"></i> Add Nearby 'N' Type Users (20 km)
                    </button>
                    <button class="btn btn-info" onclick="addNearbyDUsersToOrder(event, ${order.id})">
                        <i class="fa fa-plus"></i> Add Nearby 'D' Type Users (20 km)
                    </button>
                    <button class="btn btn-warning" onclick="addBulkNotifiedVendors(event, ${order.id})">
                        <i class="fa fa-envelope"></i> Add Bulk Notified Vendors
                    </button>
                </div>
                <small class="text-muted d-block mt-2">This will find and add all 'N' or 'D' type users within 20 km range to notified vendors, or add 5 bulk vendors from bulk_message_notifications and send SMS notifications</small>
            </div>
        </div>
    `;
    
    document.getElementById('orderDetailsContent').innerHTML = html;
}

function addNearbyNUsersToOrder(event, orderId) {
    if (!confirm('Are you sure you want to add nearby \'N\' type users (within 20 km) to this order\'s notified vendors?')) {
        return;
    }
    
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Adding...';
    
    fetch(`/api/admin/order/${orderId}/add-nearby-n-users?radius=20`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP ${response.status}: ${text.substring(0, 100)}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.status === 'success') {
            const newCount = data.data ? (data.data.new_count || 0) : 0;
            const totalCount = data.data ? (data.data.total_notified_vendors || 0) : 0;
            const totalFound = data.data ? (data.data.total_found || 0) : 0;
            const alreadyNotified = data.data ? (data.data.already_notified_count || 0) : 0;
            const isRandomSelection = data.data ? (data.data.is_random_selection || false) : false;
            
            console.log('newCount:', newCount, 'totalCount:', totalCount, 'isRandomSelection:', isRandomSelection);
            
            if (totalFound === 0 && newCount === 0 && !isRandomSelection) {
                alert('0 vendors available - No \'N\' type users found within 20 km range and no random users available.');
            } else if (isRandomSelection) {
                alert(`No 'N' type users found within 20 km range.\n\nSelected ${newCount} random 'N' type users and added to order.\n\nTotal notified vendors: ${totalCount}`);
                // Reload order details
                const orderType = 'customer_app';
                viewOrderDetails(orderId, orderType);
            } else if (newCount === 0 && totalFound > 0) {
                alert(`0 vendors available - All ${totalFound} 'N' type users within 20 km were already notified.`);
            } else {
                alert(`Success! Added ${newCount} 'N' type users to the order.\n\nTotal notified vendors: ${totalCount}\nAlready notified: ${alreadyNotified}\nTotal found: ${totalFound}`);
                // Reload order details
                const orderType = 'customer_app';
                viewOrderDetails(orderId, orderType);
            }
            button.disabled = false;
            button.innerHTML = originalText;
        } else {
            alert('Error: ' + (data.msg || 'Failed to add nearby users'));
            button.disabled = false;
            button.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to add nearby users: ' + error.message);
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

function addNearbyDUsersToOrder(event, orderId) {
    if (!confirm('Are you sure you want to add nearby \'D\' type users (within 20 km) to this order\'s notified vendors?')) {
        return;
    }
    
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Adding...';
    
    fetch(`/api/admin/order/${orderId}/add-nearby-d-users?radius=20`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP ${response.status}: ${text.substring(0, 100)}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.status === 'success') {
            const newCount = data.data ? (data.data.new_count || 0) : 0;
            const totalCount = data.data ? (data.data.total_notified_vendors || 0) : 0;
            
            console.log('newCount:', newCount, 'totalCount:', totalCount);
            
            if (newCount === 0) {
                alert('0 users found within 20 km range.\n\nNot notified - No \'D\' type users are within 20 km of this order location.');
                button.disabled = false;
                button.innerHTML = originalText;
            } else {
                alert(`Success! Added ${newCount} 'D' type users to the order.\n\nTotal notified vendors: ${totalCount}`);
                // Reload order details
                const orderType = 'customer_app'; // Default, you might want to detect this
                viewOrderDetails(orderId, orderType);
                button.disabled = false;
                button.innerHTML = originalText;
            }
        } else {
            alert('Error: ' + (data.msg || 'Failed to add nearby users'));
            button.disabled = false;
            button.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to add nearby users: ' + error.message);
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

function addBulkNotifiedVendors(event, orderId) {
    if (!confirm('Are you sure you want to find 5 nearby vendors from bulk_message_notifications and send SMS notifications?')) {
        return;
    }
    
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sending...';
    
    fetch(`/api/admin/order/${orderId}/add-bulk-notified-vendors`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP ${response.status}: ${text.substring(0, 100)}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.status === 'success') {
            const notifiedCount = data.data ? (data.data.vendors_notified || 0) : 0;
            const totalCount = data.data ? (data.data.total_selected || 0) : 0;
            
            const totalFound = data.data ? (data.data.total_found || 0) : 0;
            const alreadyNotified = data.data ? (data.data.already_notified_count || 0) : 0;
            const newNotified = data.data ? (data.data.new_notified_count || 0) : 0;
            const userIdsAdded = data.data ? (data.data.vendor_user_ids_added || 0) : 0;
            
            if (totalFound === 0) {
                alert('0 vendors available - No matching vendors found in bulk_message_notifications.');
            } else if (notifiedCount === 0 && totalCount === 0) {
                alert(`0 vendors available - All ${totalFound} matching vendors were already notified.\n\nClick the button again to find more vendors.`);
            } else if (notifiedCount === 0) {
                alert('SMS sending failed for all vendors.\n\nCheck the logs for details.');
            } else {
                let msg = `Success! Sent SMS notifications to ${notifiedCount} new bulk vendors.\n\n`;
                msg += `Total found: ${totalFound}\nAlready notified: ${alreadyNotified}\nNewly notified: ${newNotified}`;
                if (userIdsAdded > 0) {
                    msg += `\nVendor IDs added to notified list: ${userIdsAdded}`;
                }
                msg += `\n\nYou can click the button again to find and notify 5 more vendors.`;
                alert(msg);
                
                // Store bulk notified vendors in a global variable to display in order details
                if (data.data && data.data.vendors) {
                    window.bulkNotifiedVendors = window.bulkNotifiedVendors || {};
                    window.bulkNotifiedVendors[orderId] = data.data.vendors || [];
                }
                
                // Reload order details to show updated notified vendors
                const orderType = 'customer_app';
                viewOrderDetails(order.id || orderId, orderType);
            }
            button.disabled = false;
            button.innerHTML = originalText;
        } else {
            alert('Error: ' + (data.msg || 'Failed to send SMS to bulk vendors'));
            button.disabled = false;
            button.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to send SMS to bulk vendors: ' + error.message);
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

function addVendorToOrder(event, orderId, vendorId, vendorName) {
    if (!confirm(`Are you sure you want to add "${vendorName}" to the notified vendors list for this order?`)) {
        return;
    }
    
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Adding...';
    
    fetch(`/api/admin/order/${orderId}/add-vendor/${vendorId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP ${response.status}: ${text.substring(0, 100)}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.status === 'success') {
            const totalCount = data.data ? (data.data.total_notified_vendors || 0) : 0;
            const alreadyNotified = data.data ? (data.data.already_notified || false) : false;
            
            if (alreadyNotified) {
                alert(`"${vendorName}" is already in the notified vendors list.\n\nTotal notified vendors: ${totalCount}`);
            } else {
                alert(`Success! Added "${vendorName}" to the notified vendors list.\n\nTotal notified vendors: ${totalCount}`);
            }
            
            // Reload order details to show updated notified vendors
            const orderType = 'customer_app';
            viewOrderDetails(orderId, orderType);
        } else {
            alert('Error: ' + (data.msg || 'Failed to add vendor to order'));
            button.disabled = false;
            button.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to add vendor to order: ' + error.message);
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

// ==================== ORDER STATUS MANAGEMENT ====================

function updateOrderStatus(orderId) {
    const statusSelect = document.getElementById('orderStatusSelect');
    const notesInput = document.getElementById('orderStatusNotes');
    const newStatus = statusSelect.value;
    const notes = notesInput ? notesInput.value : '';
    
    const statusLabels = {
        1: 'Scheduled',
        2: 'Accepted',
        3: 'In Progress',
        4: 'Picked Up',
        5: 'Completed',
        6: 'Accepted by Other',
        7: 'Cancelled'
    };
    
    if (!confirm(`Are you sure you want to update the order status to "${statusLabels[newStatus]}"?`)) {
        return;
    }
    
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Updating...';
    
    fetch(`/api/admin/order/${orderId}/status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            status: parseInt(newStatus),
            notes: notes
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP ${response.status}: ${text.substring(0, 100)}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Update status response:', data);
        if (data.status === 'success') {
            const oldStatus = data.data ? data.data.old_status_label : 'Unknown';
            const newStatusLabel = data.data ? data.data.new_status_label : 'Unknown';
            alert(`Order status updated successfully!\n\nFrom: ${oldStatus}\nTo: ${newStatusLabel}`);
            
            // Reload order details to show updated status
            viewOrderDetails(orderId, 'customer_app');
            
            // Refresh the orders table
            if (customerAppOrdersTable) {
                customerAppOrdersTable.ajax.reload();
            }
        } else {
            alert('Error: ' + (data.msg || 'Failed to update order status'));
            button.disabled = false;
            button.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update order status: ' + error.message);
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

// ==================== VENDOR SEARCH & ASSIGNMENT ====================

function searchVendors() {
    const searchInput = document.getElementById('vendorSearchInput');
    const typeFilter = document.getElementById('vendorTypeFilter');
    const searchQuery = searchInput.value.trim();
    const vendorType = typeFilter ? typeFilter.value : '';
    
    // Use the global currentOrderId
    const orderId = currentOrderId;
    
    if (!orderId) {
        alert('No order selected. Please view an order first.');
        return;
    }
    
    if (searchQuery.length < 2) {
        alert('Please enter at least 2 characters to search');
        return;
    }
    
    const resultsDiv = document.getElementById('vendorSearchResults');
    const resultsBody = document.getElementById('vendorSearchResultsBody');
    
    resultsBody.innerHTML = '<tr><td colspan="6" class="text-center"><i class="fa fa-spinner fa-spin"></i> Searching...</td></tr>';
    resultsDiv.style.display = 'block';
    
    const params = new URLSearchParams({
        q: searchQuery,
        limit: 20
    });
    
    if (vendorType) {
        params.append('type', vendorType);
    }
    
    fetch(`/api/admin/vendors/search?${params.toString()}`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP ${response.status}: ${text.substring(0, 100)}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Search vendors response:', data);
        if (data.status === 'success' && data.data && data.data.length > 0) {
            const vendors = data.data;
            resultsBody.innerHTML = vendors.map(vendor => `
                <tr>
                    <td>${vendor.id}</td>
                    <td>${vendor.name || 'N/A'}</td>
                    <td>${vendor.mobile || 'N/A'}</td>
                    <td><span class="badge badge-secondary">${vendor.user_type || 'N/A'}</span></td>
                    <td>${vendor.shop_name || 'N/A'}</td>
                    <td>
                        <button class="btn btn-sm btn-success" onclick="assignVendorToOrder(${orderId}, ${vendor.id}, '${vendor.user_type || 'shop'}', '${(vendor.name || '').replace(/'/g, "\\'")}')">
                            <i class="fa fa-check"></i> Assign
                        </button>
                    </td>
                </tr>
            `).join('');
        } else {
            resultsBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No vendors found matching your search</td></tr>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        resultsBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Error: ${error.message}</td></tr>`;
    });
}

function getAvailableVendorsForOrder(orderId) {
    const radiusSelect = document.getElementById('nearbyRadius');
    const radius = radiusSelect ? radiusSelect.value : 20;
    
    const resultsDiv = document.getElementById('nearbyVendorsResults');
    const resultsBody = document.getElementById('nearbyVendorsResultsBody');
    
    resultsBody.innerHTML = '<tr><td colspan="6" class="text-center"><i class="fa fa-spinner fa-spin"></i> Finding nearby vendors...</td></tr>';
    resultsDiv.style.display = 'block';
    
    fetch(`/api/admin/order/${orderId}/available-vendors?radius=${radius}`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP ${response.status}: ${text.substring(0, 100)}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Available vendors response:', data);
        if (data.status === 'success' && data.data) {
            const withinRadius = data.data.vendors_within_radius || [];
            
            if (withinRadius.length > 0) {
                resultsBody.innerHTML = withinRadius.map(vendor => `
                    <tr class="${vendor.within_radius ? 'table-success' : ''}">
                        <td>${vendor.id}</td>
                        <td>${vendor.name || 'N/A'}</td>
                        <td>${vendor.shop_name || 'N/A'}</td>
                        <td><span class="badge badge-${vendor.distance_km <= 5 ? 'success' : vendor.distance_km <= 10 ? 'warning' : 'secondary'}">${vendor.distance_km} km</span></td>
                        <td><span class="badge badge-info">${vendor.user_type || 'N/A'}</span></td>
                        <td>
                            <button class="btn btn-sm btn-success" onclick="assignVendorToOrder(${orderId}, ${vendor.id}, 'shop', '${(vendor.name || '').replace(/'/g, "\\'")}')">
                                <i class="fa fa-check"></i> Assign
                            </button>
                        </td>
                    </tr>
                `).join('');
            } else {
                resultsBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No vendors found within the selected radius</td></tr>';
            }
        } else {
            resultsBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">${data.msg || 'Failed to find nearby vendors'}</td></tr>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        resultsBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Error: ${error.message}</td></tr>`;
    });
}

function assignVendorToOrder(orderId, vendorId, vendorType, vendorName) {
    if (!confirm(`Are you sure you want to assign "${vendorName}" to this order?\n\nThis will:\n- Set the order status to "Accepted"\n- Assign the vendor to this order\n- Send a notification to the vendor`)) {
        return;
    }
    
    fetch(`/api/admin/order/${orderId}/assign-vendor`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            vendor_id: vendorId,
            vendor_type: vendorType,
            notify_vendor: true
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP ${response.status}: ${text.substring(0, 100)}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Assign vendor response:', data);
        if (data.status === 'success') {
            const notificationSent = data.data ? data.data.notification_sent : false;
            alert(`Vendor assigned successfully!\n\nVendor: ${vendorName}\nStatus: Accepted\nNotification Sent: ${notificationSent ? 'Yes' : 'No'}`);
            
            // Reload order details to show updated status and vendor
            viewOrderDetails(orderId, 'customer_app');
            
            // Refresh the orders table
            if (customerAppOrdersTable) {
                customerAppOrdersTable.ajax.reload();
            }
        } else {
            alert('Error: ' + (data.msg || 'Failed to assign vendor to order'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to assign vendor: ' + error.message);
    });
}

// Store current order ID for use in vendor search
let currentOrderId = null;

// Update viewOrderDetails to store the order ID
const originalViewOrderDetails = viewOrderDetails;
viewOrderDetails = function(orderId, orderType) {
    currentOrderId = orderId;
    return originalViewOrderDetails(orderId, orderType);
};
</script>

@endsection

