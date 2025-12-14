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
                                    <th width="30%">Order Number:</th>
                                    <td>{{ $order->order_number ?? ($order->order_no ?? ($order->id ?? 'N/A')) }}</td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        @php
                                            $status = isset($order->status) ? (int)$order->status : 0;
                                            $statusText = match($status) {
                                                0 => 'Request Pending',
                                                1 => 'Accepted',
                                                2 => 'Processing',
                                                3 => 'Out for Delivery',
                                                4 => 'Delivered',
                                                5 => 'Cancelled',
                                                default => 'Unknown'
                                            };
                                        @endphp
                                        {{ $statusText }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Order Date:</th>
                                    <td>
                                        @if(isset($order->date))
                                            {{ date('d-m-Y', strtotime($order->date)) }}
                                        @elseif(isset($order->created_at))
                                            {{ date('d-m-Y', strtotime($order->created_at)) }}
                                        @elseif(isset($order->order_date))
                                            {{ date('d-m-Y', strtotime($order->order_date)) }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Estimated Weight:</th>
                                    <td>{{ $order->estim_weight ?? ($order->estimated_weight ?? 'N/A') }}</td>
                                </tr>
                                <tr>
                                    <th>Estimated Price:</th>
                                    <td>{{ $order->estim_price ?? ($order->estimated_price ?? 'N/A') }}</td>
                                </tr>
                                <tr>
                                    <th>Delivery Type:</th>
                                    <td>{{ $order->del_type ?? ($order->delivery_type ?? 'N/A') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            {{-- Customer Information Section --}}
            <div class="row mt-4">
                <div class="col-12">
                    <h5 class="mb-3">Customer Information</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th width="30%">Name:</th>
                                    <td>
                                        @php
                                            $customerName = 'N/A';
                                            if (isset($order->customerdetails)) {
                                                if (is_object($order->customerdetails)) {
                                                    $customerName = $order->customerdetails->name ?? ($order->customerdetails->customer_name ?? 'N/A');
                                                } elseif (is_array($order->customerdetails)) {
                                                    $customerName = $order->customerdetails['name'] ?? ($order->customerdetails['customer_name'] ?? 'N/A');
                                                }
                                            }
                                        @endphp
                                        {{ $customerName }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Contact:</th>
                                    <td>
                                        @php
                                            $customerContact = 'N/A';
                                            if (isset($order->customerdetails)) {
                                                if (is_object($order->customerdetails)) {
                                                    $customerContact = $order->customerdetails->contact ?? ($order->customerdetails->phone ?? ($order->customerdetails->mob_num ?? 'N/A'));
                                                } elseif (is_array($order->customerdetails)) {
                                                    $customerContact = $order->customerdetails['contact'] ?? ($order->customerdetails['phone'] ?? ($order->customerdetails['mob_num'] ?? 'N/A'));
                                                }
                                            }
                                        @endphp
                                        {{ $customerContact }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Address:</th>
                                    <td>
                                        @php
                                            $customerAddress = 'N/A';
                                            if (isset($order->customerdetails)) {
                                                if (is_object($order->customerdetails)) {
                                                    $customerAddress = $order->customerdetails->address ?? 'N/A';
                                                } elseif (is_array($order->customerdetails)) {
                                                    $customerAddress = $order->customerdetails['address'] ?? 'N/A';
                                                }
                                            }
                                        @endphp
                                        {{ $customerAddress }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
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
        @else
            <div class="alert alert-warning">
                <p>Order details not found.</p>
            </div>
        @endif
    </div>
</div>
