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

function viewBulkOrderDetails(orderId) {
    // Show modal for bulk scrap request details
    $('#orderDetailsModal').modal('show');
    
    // Set loading state
    document.getElementById('orderDetailsContent').innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-2">Loading bulk order details...</p>
        </div>
    `;
    
    // Fetch bulk order details from API
    fetch(`/api/dashboard/bulk-order/${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data) {
                displayBulkOrderDetails(data.data);
            } else {
                showError(data.msg || 'Failed to load bulk order details');
            }
        })
        .catch(error => {
            console.error('Error fetching bulk order details:', error);
            showError('Failed to load bulk order details. Please try again.');
        });
}

function displayBulkOrderDetails(order) {
    const orderDate = order.created_at ? new Date(order.created_at).toLocaleDateString() : 'N/A';
    const status = order.status || 'active';
    const statusColor = getStatusColor(status);
    const orderType = order.order_type || 'bulk_buy';
    const orderTypeLabel = order.order_type_label || 'Bulk Buy';
    
    // Type badge color
    let typeBadgeColor = 'info';
    if (orderType === 'bulk_sell') typeBadgeColor = 'success';
    if (orderType === 'pending_buy') typeBadgeColor = 'warning';
    
    // Parse subcategories
    let subcategoriesHtml = 'N/A';
    if (order.subcategories) {
        try {
            const subcats = typeof order.subcategories === 'string' ? JSON.parse(order.subcategories) : order.subcategories;
            if (Array.isArray(subcats) && subcats.length > 0) {
                subcategoriesHtml = '<ul class="list-unstyled mb-0">' + 
                    subcats.map(s => `<li>${s.subcategory_name || s.name || s}</li>`).join('') + 
                    '</ul>';
            }
        } catch (e) {
            subcategoriesHtml = order.subcategories;
        }
    }
    
    // Price field varies by table
    let priceDisplay = 'N/A';
    if (order.preferred_price) {
        priceDisplay = '₹' + order.preferred_price + '/kg';
    } else if (order.asking_price) {
        priceDisplay = '₹' + order.asking_price + '/kg';
    }
    
    // User label based on order type
    const userLabel = orderType === 'bulk_sell' ? 'Seller' : 'Buyer';
    
    document.getElementById('orderDetailsContent').innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6>Request Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>Request ID:</strong></td><td>${order.id}</td></tr>
                    <tr><td><strong>Type:</strong></td><td><span class="badge badge-${typeBadgeColor}">${orderTypeLabel}</span></td></tr>
                    <tr><td><strong>Status:</strong></td><td><span class="badge badge-${statusColor}">${status}</span></td></tr>
                    <tr><td><strong>Created:</strong></td><td>${orderDate}</td></tr>
                    <tr><td><strong>Scrap Type:</strong></td><td>${order.scrap_type || 'N/A'}</td></tr>
                    <tr><td><strong>Quantity:</strong></td><td>${order.quantity ? order.quantity + ' kg' : 'N/A'}</td></tr>
                    <tr><td><strong>Price:</strong></td><td>${priceDisplay}</td></tr>
                    <tr><td><strong>Preferred Distance:</strong></td><td>${order.preferred_distance ? order.preferred_distance + ' km' : 'N/A'}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>${userLabel} Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>${userLabel} ID:</strong></td><td>${order.buyer_id || 'N/A'}</td></tr>
                    <tr><td><strong>${userLabel} Name:</strong></td><td>${order.buyer_name || 'N/A'}</td></tr>
                    ${order.payment_status ? `<tr><td><strong>Payment Status:</strong></td><td><span class="badge badge-${order.payment_status === 'paid' ? 'success' : 'warning'}">${order.payment_status}</span></td></tr>` : ''}
                    ${order.transaction_id ? `<tr><td><strong>Transaction ID:</strong></td><td>${order.transaction_id}</td></tr>` : ''}
                </table>
                <h6 class="mt-3">Location</h6>
                <p>${order.location || 'N/A'}</p>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-12">
                <h6>Subcategories</h6>
                ${subcategoriesHtml}
            </div>
        </div>
        ${order.additional_notes ? `
        <div class="row mt-3">
            <div class="col-md-12">
                <h6>Additional Notes</h6>
                <p>${order.additional_notes}</p>
            </div>
        </div>
        ` : ''}
    `;
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
    
    // Normalize possible sparse arrays/objects from API so count and rendered rows always match.
    const normalizeVendors = (vendors) => {
        if (Array.isArray(vendors)) {
            return vendors.filter(vendor => vendor && typeof vendor === 'object');
        }
        if (vendors && typeof vendors === 'object') {
            return Object.values(vendors).filter(vendor => vendor && typeof vendor === 'object');
        }
        return [];
    };
    const parseNotifiedVendorIds = (rawIds) => {
        if (!rawIds) return [];
        try {
            const parsed = typeof rawIds === 'string' ? JSON.parse(rawIds) : rawIds;
            if (!Array.isArray(parsed)) return [];
            return parsed
                .map(id => (typeof id === 'string' ? parseInt(id, 10) : id))
                .filter(id => Number.isFinite(id) && id > 0);
        } catch (e) {
            return [];
        }
    };
    const monthlySubscribedVendors = normalizeVendors(order.monthly_subscribed_vendors);
    const notifiedVendors = normalizeVendors(order.notified_vendors);
    const notifiedVendorIds = parseNotifiedVendorIds(order.notified_vendor_ids);
    
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
            
            ${monthlySubscribedVendors.length > 0 ? `
            <h5 class="mb-3 mt-4">Monthly Subscribed Vendors (${monthlySubscribedVendors.length})</h5>
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
                        ${monthlySubscribedVendors.map(vendor => {
                            const isAlreadyNotified = notifiedVendors.some(nv => parseInt(nv.id) === parseInt(vendor.id));
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
            
            ${notifiedVendors.length > 0 ? `
            <h5 class="mb-3 mt-4">Notified Vendors (${notifiedVendors.length}) <small class="text-muted">- Sorted by distance</small></h5>
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
                        ${notifiedVendors.map((vendor, index) => `
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
            ${notifiedVendorIds.length > 0 ? `
            <div class="alert alert-warning mt-4">
                <i class="fa fa-exclamation-triangle"></i> Notifications were sent to ${notifiedVendorIds.length} vendor(s), but full vendor details are not available in this response yet.
            </div>
            <h5 class="mb-3 mt-3">Notified Vendor IDs (${notifiedVendorIds.length})</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Vendor ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${notifiedVendorIds.map((vendorId, index) => `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${vendorId}</td>
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
                    ${parseInt(order.status) === 1 ? `
                    <div class="mt-3">
                        <button class="btn btn-warning" onclick="rescheduleScheduledOrder(event, ${order.id})">
                            <i class="fa fa-calendar"></i> Reschedule & Re-Notify Vendors
                        </button>
                        <small class="text-muted d-block mt-2">For scheduled orders, this will send notifications again to all previously notified vendors.</small>
                    </div>
                    ` : ''}
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

function rescheduleScheduledOrder(event, orderId) {
    const notesInput = document.getElementById('orderStatusNotes');
    const notes = notesInput ? notesInput.value : '';

    if (!confirm('Are you sure you want to reschedule this scheduled order and notify all previously notified vendors again?')) {
        return;
    }

    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Rescheduling...';

    fetch(`/api/admin/order/${orderId}/reschedule-scheduled`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            notes: notes
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP ${response.status}: ${text.substring(0, 150)}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Reschedule response:', data);
        if (data.status === 'success') {
            const notifications = data.data?.notifications || {};
            const pushSent = notifications.pushSent || 0;
            const smsSent = notifications.smsSent || 0;
            const totalVendors = notifications.totalVendors || 0;

            alert(
                `Order rescheduled successfully.\n\n` +
                `Notified vendors: ${totalVendors}\n` +
                `Push sent: ${pushSent}\n` +
                `SMS sent: ${smsSent}`
            );

            viewOrderDetails(orderId, 'customer_app');
            if (customerAppOrdersTable) {
                customerAppOrdersTable.ajax.reload();
            }
            return;
        }

        alert('Error: ' + (data.msg || 'Failed to reschedule order'));
        button.disabled = false;
        button.innerHTML = originalText;
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to reschedule order: ' + error.message);
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
