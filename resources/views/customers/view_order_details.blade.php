<div class="card">
    <div class="card-body">
        @if(isset($order) && $order)
            <ul class="d-sm-flex d-block align-items-start justify-content-between mb-5">
                <li class="food-media">
                    @php
                        $deliveryBoyName = 'N/A';
                        $deliveryBoyImage = '';
                        if (isset($deliveryBoy) && $deliveryBoy) {
                            $deliveryBoyName = $deliveryBoy->name ?? ($deliveryBoy->delivery_boy->name ?? 'N/A');
                            $deliveryBoyImage = $deliveryBoy->profile_img ?? ($deliveryBoy->delivery_boy->profile_img ?? '');
                        }
                        // Handle image URL - if it's a full URL, use it; otherwise, try to construct a proper path
                        if ($deliveryBoyImage && !filter_var($deliveryBoyImage, FILTER_VALIDATE_URL)) {
                            // If it's not a full URL, it might be a relative path - use as is or construct URL
                            if (strpos($deliveryBoyImage, 'http') !== 0) {
                                // Assume it's already a full URL from the API, or use as-is
                            }
                        }
                    @endphp
                    @if($deliveryBoyImage)
                        <img src="{{ $deliveryBoyImage }}" class="rounded" alt="Delivery Boy" style="width: 100px; height: 100px; object-fit: cover;" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\'%3E%3Crect fill=\'%23ddd\' width=\'100\' height=\'100\'/%3E%3Ctext fill=\'%23999\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\'%3ENo Image%3C/text%3E%3C/svg%3E';">
                    @else
                        <div class="rounded" style="width: 100px; height: 100px; background-color: #ddd; display: flex; align-items: center; justify-content: center; color: #999;">No Image</div>
                    @endif
                </li>
                <li class="ms-sm-3 ms-0">
                    <h4 class="heading">{{ $deliveryBoyName }}</h4>
                    <p>Delivery Boy</p>

                    <div class="row">
                        <div class="col-6">
                            <p class="mb-0">E-mail: 
                                @if(isset($deliveryBoy) && $deliveryBoy)
                                    <span>{{ $deliveryBoy->email ?? ($deliveryBoy->delivery_boy->email ?? 'N/A') }}</span>
                                @else
                                    <span>N/A</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-6">
                            <p class="mb-0">Phone: 
                                @if(isset($deliveryBoy) && $deliveryBoy)
                                    <span>{{ $deliveryBoy->contact ?? ($deliveryBoy->delivery_boy->contact ?? ($deliveryBoy->mob_num ?? 'N/A')) }}</span>
                                @else
                                    <span>N/A</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <p class="mb-0">Age:
                                @if(isset($deliveryBoy) && $deliveryBoy)
                                    <span>{{ $deliveryBoy->age ?? ($deliveryBoy->delivery_boy->age ?? 'N/A') }}</span>
                                @else
                                    <span>N/A</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-6">
                            <p class="mb-0">DOB: 
                                @if(isset($deliveryBoy) && $deliveryBoy)
                                    @php
                                        $dob = $deliveryBoy->dob ?? ($deliveryBoy->delivery_boy->dob ?? null);
                                        if ($dob) {
                                            try {
                                                $dobFormatted = date('d-m-Y', strtotime($dob));
                                            } catch (\Exception $e) {
                                                $dobFormatted = $dob;
                                            }
                                        } else {
                                            $dobFormatted = 'N/A';
                                        }
                                    @endphp
                                    <span>{{ $dobFormatted }}</span>
                                @else
                                    <span>N/A</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <p class="mb-0">Licence No: 
                                @if(isset($deliveryBoy) && $deliveryBoy)
                                    <span>{{ $deliveryBoy->licence_no ?? ($deliveryBoy->delivery_boy->licence_no ?? ($deliveryBoy->driving_license_number ?? 'N/A')) }}</span>
                                @else
                                    <span>N/A</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-12">
                            <p class="mb-0">Address: 
                                @if(isset($deliveryBoy) && $deliveryBoy)
                                    <span>{{ $deliveryBoy->address ?? ($deliveryBoy->delivery_boy->address ?? 'N/A') }}</span>
                                @else
                                    <span>N/A</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </li>
            </ul>
            
            {{-- Order Information Section --}}
            <div class="row mt-4">
                <div class="col-12">
                    <h5 class="mb-3">Order Information</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th width="30%">Order ID:</th>
                                    <td>{{ $order->id ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th width="30%">Order Number:</th>
                                    <td>{{ $order->order_number ?? ($order->order_no ?? ($order->id ?? 'N/A')) }}</td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        @php
                                            $status = isset($order->status) ? (int)$order->status : 0;
                                            $statusText = match($status) {
                                                1 => 'Scheduled',
                                                2 => 'Accepted',
                                                3 => 'In Progress',
                                                4 => 'Picked Up',
                                                5 => 'Completed',
                                                6 => 'Accepted by Other',
                                                7 => 'Cancelled',
                                                default => 'Unknown'
                                            };
                                            $statusBadgeClass = match($status) {
                                                1 => 'bg-secondary',
                                                2 => 'bg-primary',
                                                3 => 'bg-info',
                                                4 => 'bg-warning',
                                                5 => 'bg-success',
                                                6 => 'bg-dark',
                                                7 => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $statusBadgeClass }}">{{ $statusText }}</span>
                                        
                                        {{-- Show Revert to Scheduled button if status is Accepted (2) --}}
                                        @if($status === 2)
                                            <button type="button" class="btn btn-warning btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#revertToScheduledModal">
                                                <i class="fa fa-undo"></i> Revert to Scheduled
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Date:</th>
                                    <td>
                                        @if(isset($order->date))
                                            {{ date('n/j/Y', strtotime($order->date)) }}
                                        @elseif(isset($order->created_at))
                                            {{ date('n/j/Y', strtotime($order->created_at)) }}
                                        @elseif(isset($order->order_date))
                                            {{ date('n/j/Y', strtotime($order->order_date)) }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Estimated Weight:</th>
                                    <td>{{ $order->estim_weight ?? ($order->estimated_weight ?? 'N/A') }} kg</td>
                                </tr>
                                <tr>
                                    <th>Estimated Price:</th>
                                    <td>₹{{ number_format(floatval($order->estim_price ?? ($order->estimated_price ?? 0)), 2) }}</td>
                                </tr>
                                <tr>
                                    <th>Total Amount:</th>
                                    <td>
                                        @php
                                            $totalAmount = $order->total_amount ?? ($order->estim_price ?? ($order->estimated_price ?? ($order->amount ?? 0)));
                                            $totalAmount = is_numeric($totalAmount) ? floatval($totalAmount) : 0;
                                        @endphp
                                        <strong>₹{{ number_format($totalAmount, 2) }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Delivery Type:</th>
                                    <td>{{ $order->del_type ?? ($order->delivery_type ?? 'N/A') }}</td>
                                </tr>
                                @if(isset($order->preferred_pickup_time) && !empty($order->preferred_pickup_time))
                                <tr>
                                    <th>Preferred Pickup Time:</th>
                                    <td>{{ $order->preferred_pickup_time }}</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            {{-- Customer Information Section --}}
            @php
                // Ensure customerdetails is parsed if it's a string
                if (isset($order->customerdetails) && is_string($order->customerdetails)) {
                    try {
                        $json = json_decode($order->customerdetails, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                            $order->customerdetails = (object)$json;
                        } else {
                            // If JSON parse fails, try with stripslashes
                            $cleaned = stripslashes($order->customerdetails);
                            $json = json_decode($cleaned, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                                $order->customerdetails = (object)$json;
                            }
                        }
                    } catch (\Exception $e) {
                        // If parsing fails, treat as address string
                    }
                }
                
                // Extract customer info
                $customerName = 'N/A';
                $customerContact = 'N/A';
                $customerAddress = 'N/A';
                
                if (isset($order->customerdetails)) {
                    if (is_object($order->customerdetails)) {
                        $customerName = $order->customerdetails->name ?? ($order->customerdetails->customer_name ?? ($order->customerdetails->full_name ?? 'N/A'));
                        $customerContact = $order->customerdetails->contact ?? ($order->customerdetails->phone ?? ($order->customerdetails->mobile ?? ($order->customerdetails->mob_num ?? ($order->customerdetails->phone_number ?? 'N/A'))));
                        $customerAddress = $order->customerdetails->address ?? ($order->customerdetails->full_address ?? 'N/A');
                    } elseif (is_array($order->customerdetails)) {
                        $customerName = $order->customerdetails['name'] ?? ($order->customerdetails['customer_name'] ?? ($order->customerdetails['full_name'] ?? 'N/A'));
                        $customerContact = $order->customerdetails['contact'] ?? ($order->customerdetails['phone'] ?? ($order->customerdetails['mobile'] ?? ($order->customerdetails['mob_num'] ?? ($order->customerdetails['phone_number'] ?? 'N/A'))));
                        $customerAddress = $order->customerdetails['address'] ?? ($order->customerdetails['full_address'] ?? 'N/A');
                    } elseif (is_string($order->customerdetails)) {
                        // If it's still a string, treat as address
                        $customerAddress = $order->customerdetails;
                    }
                }
                
                // Also check direct order fields
                if ($customerName === 'N/A' && isset($order->customer_name) && !empty($order->customer_name)) {
                    $customerName = $order->customer_name;
                }
                if ($customerContact === 'N/A' && isset($order->customer_phone) && !empty($order->customer_phone)) {
                    $customerContact = $order->customer_phone;
                }
                if ($customerAddress === 'N/A' && isset($order->customer_address) && !empty($order->customer_address)) {
                    $customerAddress = $order->customer_address;
                }
            @endphp
            <div class="row mt-4">
                <div class="col-12">
                    <h5 class="mb-3">Customer Information</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th width="30%">Customer ID:</th>
                                    <td>{{ $order->customer_id ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th width="30%">Name:</th>
                                    <td>{{ $customerName }}</td>
                                </tr>
                                <tr>
                                    <th>Phone:</th>
                                    <td>{{ $customerContact }}</td>
                                </tr>
                                <tr>
                                    <th>Address:</th>
                                    <td>{{ $customerAddress }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            {{-- Order Items Section --}}
            @php
                $orderItems = [];
                if (isset($order->orderdetails)) {
                    if (is_string($order->orderdetails)) {
                        try {
                            $orderDetailsJson = json_decode($order->orderdetails, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $order->orderdetails = $orderDetailsJson;
                            }
                        } catch (\Exception $e) {
                            // Keep as string if parsing fails
                        }
                    }
                    
                    if (is_array($order->orderdetails)) {
                        $orderItems = $order->orderdetails;
                    } elseif (is_object($order->orderdetails) && isset($order->orderdetails->orders)) {
                        // Handle nested structure: { orders: { category: [items] } }
                        foreach ($order->orderdetails->orders as $category => $subcats) {
                            if (is_array($subcats)) {
                                $orderItems = array_merge($orderItems, $subcats);
                            } elseif (is_object($subcats)) {
                                foreach ($subcats as $items) {
                                    if (is_array($items)) {
                                        $orderItems = array_merge($orderItems, $items);
                                    }
                                }
                            }
                        }
                    }
                }
            @endphp
            
            @if(count($orderItems) > 0)
            <div class="row mt-4">
                <div class="col-12">
                    <h5 class="mb-3">Order Items</h5>
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
                                @foreach($orderItems as $item)
                                    @php
                                        $itemObj = is_object($item) ? $item : (object)$item;
                                        
                                        // Get item name - check multiple field names (material_name is used in v2 orders)
                                        $itemName = $itemObj->material_name ?? $itemObj->name ?? $itemObj->category_name ?? $itemObj->item_name ?? $itemObj->subcategory_name ?? 'N/A';
                                        
                                        // Get weight - check multiple field names (expected_weight_kg is used in v2 orders)
                                        $weight = $itemObj->expected_weight_kg ?? $itemObj->expected_weight ?? $itemObj->approximate_weight ?? $itemObj->weight ?? $itemObj->approximateWeight ?? $itemObj->estimated_weight ?? $itemObj->actual_weight_kg ?? $itemObj->actual_weight ?? null;
                                        $weightValue = $weight !== null ? (is_numeric($weight) ? floatval($weight) : null) : null;
                                        
                                        // Get price per kg (price_per_kg is used in v2 orders)
                                        $amountPerKg = $itemObj->price_per_kg ?? $itemObj->pricePerKg ?? $itemObj->amount_per_kg ?? $itemObj->amountPerKg ?? $itemObj->rate ?? $itemObj->price_unit ?? 0;
                                        $amountPerKgValue = is_numeric($amountPerKg) ? floatval($amountPerKg) : 0;
                                        
                                        // Calculate total price: weight * price_per_kg
                                        $totalPrice = 0;
                                        if ($weightValue !== null && $amountPerKgValue > 0) {
                                            $totalPrice = $weightValue * $amountPerKgValue;
                                        } else {
                                            // Fallback to direct price field
                                            $totalPrice = floatval($itemObj->price ?? $itemObj->total_price ?? $itemObj->amount ?? 0);
                                        }
                                        
                                        $weightDisplay = $weightValue !== null ? $weightValue . ' kg' : 'N/A';
                                    @endphp
                                    <tr>
                                        <td>{{ $itemName }}</td>
                                        <td>{{ $itemObj->quantity ?? $itemObj->qty ?? '-' }}</td>
                                        <td>{{ $weightDisplay }}</td>
                                        <td>₹{{ number_format($totalPrice, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
            
            {{-- Scrap Images Section --}}
            <div class="row mt-4">
                <div class="col-12">
                    <h5 class="mb-3">Scrap Images</h5>
                    <div class="row">
                        @php
                            $scrapImages = [];
                            
                            // Check if images are in an array format
                            if (isset($order->images) && is_array($order->images)) {
                                foreach ($order->images as $img) {
                                    if (!empty($img)) {
                                        $scrapImages[] = $img;
                                    }
                                }
                            }
                            
                            // Also check individual image fields (image1, image2, etc.)
                            for ($i = 1; $i <= 6; $i++) {
                                $imageField = 'image' . $i;
                                if (isset($order->$imageField) && !empty($order->$imageField)) {
                                    $imageUrl = $order->$imageField;
                                    // Ensure it's a valid URL or add to array if not already included
                                    if (filter_var($imageUrl, FILTER_VALIDATE_URL) || strpos($imageUrl, 'http') === 0) {
                                        // Only add if not already in array
                                        if (!in_array($imageUrl, $scrapImages)) {
                                            $scrapImages[] = $imageUrl;
                                        }
                                    }
                                }
                            }
                        @endphp
                        @if(count($scrapImages) > 0)
                            @foreach($scrapImages as $index => $imageUrl)
                                <div class="col-md-4 col-sm-6 mb-3">
                                    <div class="card shadow-sm" style="border: 1px solid #e0e0e0;">
                                        <a href="{{ $imageUrl }}" target="_blank" class="image-gallery-item" style="text-decoration: none; display: block;">
                                            <img src="{{ $imageUrl }}" 
                                                 class="card-img-top" 
                                                 alt="Scrap Image {{ $index + 1 }}" 
                                                 style="width: 100%; height: 250px; object-fit: cover; cursor: pointer; transition: transform 0.2s;"
                                                 onmouseover="this.style.transform='scale(1.02)'"
                                                 onmouseout="this.style.transform='scale(1)'"
                                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'300\' height=\'250\'%3E%3Crect fill=\'%23ddd\' width=\'300\' height=\'250\'/%3E%3Ctext fill=\'%23999\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\'%3EImage Not Found%3C/text%3E%3C/svg%3E'; this.onerror=null;">
                                        </a>
                                        <div class="card-body p-2 text-center">
                                            <small class="text-muted">Image {{ $index + 1 }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <p class="mb-0"><i class="fa fa-info-circle"></i> No scrap images uploaded for this order.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
            {{-- Notified Vendors Section --}}
            @php
                $notifiedVendors = [];
                if (isset($order->notified_vendors) && is_array($order->notified_vendors)) {
                    $notifiedVendors = $order->notified_vendors;
                } elseif (isset($order->notified_vendors) && is_object($order->notified_vendors)) {
                    $notifiedVendors = (array)$order->notified_vendors;
                }
            @endphp
            @if(count($notifiedVendors) > 0)
            <div class="row mt-4">
                <div class="col-12">
                    <h5 class="mb-3">Notified Vendors ({{ count($notifiedVendors) }}) <small class="text-muted">- Sorted by distance</small></h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
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
                                @foreach($notifiedVendors as $index => $vendor)
                                    @php
                                        $vendorObj = is_object($vendor) ? $vendor : (object)$vendor;
                                        $distance = $vendorObj->distance_km ?? null;
                                        $badgeClass = 'light';
                                        if ($distance !== null) {
                                            if ($distance <= 5) $badgeClass = 'success';
                                            elseif ($distance <= 10) $badgeClass = 'warning';
                                            else $badgeClass = 'secondary';
                                        }
                                    @endphp
                                    <tr class="{{ $index < 5 ? 'table-success' : '' }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $vendorObj->id ?? 'N/A' }}</td>
                                        <td>{{ $vendorObj->name ?? 'N/A' }}</td>
                                        <td>{{ $vendorObj->shop_name ?? 'N/A' }}</td>
                                        <td>{{ $vendorObj->mobile ?? 'N/A' }}</td>
                                        <td>
                                            @if($distance !== null)
                                                <span class="badge badge-{{ $badgeClass }}">{{ $distance }} km</span>
                                            @else
                                                <span class="badge badge-light">N/A</span>
                                            @endif
                                        </td>
                                        <td><span class="badge badge-secondary">{{ $vendorObj->user_type ?? 'N/A' }}</span></td>
                                        <td>
                                            @php
                                                $appVersion = $vendorObj->app_version ?? 'N/A';
                                                $versionBadge = $appVersion === 'v2' ? 'primary' : 'secondary';
                                            @endphp
                                            <span class="badge badge-{{ $versionBadge }}">{{ $appVersion }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
            
            {{-- Shop Information Section --}}
            <div class="row mt-4">
                <div class="col-12">
                    <h5 class="mb-3">Shop Information</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th width="30%">Shop Name:</th>
                                    <td>
                                        @php
                                            $shopName = 'N/A';
                                            if (isset($order->shopdetails)) {
                                                if (is_object($order->shopdetails)) {
                                                    $shopName = $order->shopdetails->shopname ?? ($order->shopdetails->shop_name ?? ($order->shopdetails->name ?? 'N/A'));
                                                } elseif (is_array($order->shopdetails)) {
                                                    $shopName = $order->shopdetails['shopname'] ?? ($order->shopdetails['shop_name'] ?? ($order->shopdetails['name'] ?? 'N/A'));
                                                }
                                            }
                                        @endphp
                                        {{ $shopName }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Contact:</th>
                                    <td>
                                        @php
                                            $shopContact = 'N/A';
                                            if (isset($order->shopdetails)) {
                                                if (is_object($order->shopdetails)) {
                                                    $shopContact = $order->shopdetails->contact ?? ($order->shopdetails->phone ?? ($order->shopdetails->mob_num ?? 'N/A'));
                                                } elseif (is_array($order->shopdetails)) {
                                                    $shopContact = $order->shopdetails['contact'] ?? ($order->shopdetails['phone'] ?? ($order->shopdetails['mob_num'] ?? 'N/A'));
                                                }
                                            }
                                        @endphp
                                        {{ $shopContact }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Address:</th>
                                    <td>
                                        @php
                                            $shopAddress = 'N/A';
                                            if (isset($order->shopdetails)) {
                                                if (is_object($order->shopdetails)) {
                                                    $shopAddress = $order->shopdetails->address ?? 'N/A';
                                                } elseif (is_array($order->shopdetails)) {
                                                    $shopAddress = $order->shopdetails['address'] ?? 'N/A';
                                                }
                                            }
                                        @endphp
                                        {{ $shopAddress }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            {{-- Revert to Scheduled Modal --}}
            @if(isset($order->status) && (int)$order->status === 2)
            <div class="modal fade" id="revertToScheduledModal" tabindex="-1" aria-labelledby="revertToScheduledModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="revertToScheduledModalLabel">
                                <i class="fa fa-undo text-warning"></i> Revert Order to Scheduled
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle"></i> 
                                <strong>Warning:</strong> This will revert the order from <strong>Accepted</strong> back to <strong>Scheduled</strong> status.
                            </div>
                            <p>
                                <strong>Order ID:</strong> {{ $order->id ?? 'N/A' }}<br>
                                <strong>Order Number:</strong> {{ $order->order_number ?? ($order->order_no ?? 'N/A') }}
                            </p>
                            <p class="text-muted">
                                When you revert this order:
                            </p>
                            <ul class="text-muted">
                                <li>The order will become available for all nearby vendors to accept</li>
                                <li>The currently assigned vendor will be removed</li>
                                <li>All previously notified vendors will receive a fresh notification</li>
                                <li>SMS notifications will be sent to all vendors</li>
                            </ul>
                            <div class="mb-3">
                                <label for="adminNotes" class="form-label">Admin Notes (optional)</label>
                                <textarea class="form-control" id="adminNotes" rows="3" placeholder="Add notes about why this order is being reverted..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-warning" id="confirmRevertBtn" onclick="revertOrderToScheduled()">
                                <i class="fa fa-undo"></i> Confirm Revert to Scheduled
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
                function revertOrderToScheduled() {
                    const orderId = '{{ $order->id ?? '' }}';
                    const notes = document.getElementById('adminNotes').value;
                    
                    if (!orderId) {
                        alert('Error: Order ID not found');
                        return;
                    }
                    
                    // Disable button to prevent double submission
                    const btn = document.getElementById('confirmRevertBtn');
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
                    
                    // Make API call to Node.js backend
                    fetch('{{ env('NODE_API_URL', 'http://localhost:3001') }}/admin/order/' + orderId + '/status', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'api-key': '{{ env('NODE_API_KEY', '') }}'
                        },
                        body: JSON.stringify({
                            status: 1, // Scheduled
                            notes: notes
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Show success message
                            alert('Order successfully reverted to Scheduled!\n\n' + 
                                  'Push notifications sent: ' + (data.data?.notifications?.pushSent || 0) + '\n' +
                                  'SMS notifications sent: ' + (data.data?.notifications?.smsSent || 0));
                            
                            // Close modal
                            const modal = bootstrap.Modal.getInstance(document.getElementById('revertToScheduledModal'));
                            modal.hide();
                            
                            // Reload page to show updated status
                            location.reload();
                        } else {
                            alert('Error: ' + (data.msg || 'Failed to revert order status'));
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fa fa-undo"></i> Confirm Revert to Scheduled';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error: ' + error.message);
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa fa-undo"></i> Confirm Revert to Scheduled';
                    });
                }
            </script>
            @endif
            
        @else
            <div class="alert alert-warning">
                <p>Order details not found.</p>
            </div>
        @endif
    </div>
</div>
