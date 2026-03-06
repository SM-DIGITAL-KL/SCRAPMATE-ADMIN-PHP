@extends('index')
@section('content')

<div class="content-body">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        @include('layouts.flashmessage')
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title mb-0">Create Order</h4>
                            <a href="{{ route('orders') }}" class="btn btn-outline-secondary btn-sm">Back to Orders</a>
                        </div>
                        <hr>

                        <form action="{{ route('orders.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="user_id" class="form-label">Select User</label>
                                    <select class="form-control select2 @error('user_id') is-invalid @enderror" id="user_id" name="user_id" required>
                                        <option value="">Choose user</option>
                                        @foreach($users as $user)
                                            <option
                                                value="{{ $user['id'] }}"
                                                data-address="{{ $user['address'] }}"
                                                data-latitude="{{ $user['latitude'] }}"
                                                data-longitude="{{ $user['longitude'] }}"
                                                {{ old('user_id') == $user['id'] ? 'selected' : '' }}
                                            >
                                                {{ $user['name'] ?: ('User ' . $user['id']) }}
                                                (ID: {{ $user['id'] }}, {{ $user['phone'] ?: 'No phone' }}{{ !empty($user['email']) ? ', ' . $user['email'] : '' }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Type name, phone, email, or ID to search user.</small>
                                    @error('user_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="category_ids" class="form-label">Select Categories</label>
                                    <select class="form-control select2 @error('category_ids') is-invalid @enderror @error('category_ids.*') is-invalid @enderror" id="category_ids" name="category_ids[]" multiple required>
                                        @foreach($categories as $category)
                                            <option value="{{ $category['id'] }}" {{ in_array((string)$category['id'], array_map('strval', old('category_ids', [])), true) ? 'selected' : '' }}>
                                                {{ $category['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Select one or more categories.</small>
                                    @error('category_ids')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    @error('category_ids.*')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="subcategory_ids" class="form-label">Select Subcategories</label>
                                    <input
                                        type="text"
                                        class="form-control form-control-sm mb-2"
                                        id="subcategory_search"
                                        placeholder="Search subcategories..."
                                    >
                                    <select class="form-control select2 @error('subcategory_ids') is-invalid @enderror @error('subcategory_ids.*') is-invalid @enderror" id="subcategory_ids" name="subcategory_ids[]" multiple required>
                                        @foreach($subcategories as $subcategory)
                                            <option
                                                value="{{ $subcategory['id'] }}"
                                                data-main-category-id="{{ $subcategory['main_category_id'] }}"
                                                data-default-price="{{ $subcategory['default_price'] }}"
                                                data-price-unit="{{ $subcategory['price_unit'] }}"
                                                {{ in_array((string)$subcategory['id'], array_map('strval', old('subcategory_ids', [])), true) ? 'selected' : '' }}
                                            >
                                                {{ $subcategory['main_category_name'] }} - {{ $subcategory['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Select one or more subcategories.</small>
                                    @error('subcategory_ids')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    @error('subcategory_ids.*')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label">Estimated Weight per Subcategory (optional)</label>
                                    <div id="subcategoryWeightsContainer" class="row g-2"></div>
                                    <small class="text-muted">Add weight for each selected subcategory. Total Estimated Weight is auto-summed.</small>
                                    @error('subcategory_weights')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    @error('subcategory_weights.*')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="latitude" class="form-label">Latitude</label>
                                    <input type="number" step="any" class="form-control @error('latitude') is-invalid @enderror" id="latitude" name="latitude" value="{{ old('latitude') }}" required>
                                    @error('latitude')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="longitude" class="form-label">Longitude</label>
                                    <input type="number" step="any" class="form-control @error('longitude') is-invalid @enderror" id="longitude" name="longitude" value="{{ old('longitude') }}" required>
                                    @error('longitude')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="estim_weight" class="form-label">Estimated Weight (optional)</label>
                                    <input type="number" step="0.01" min="0" class="form-control @error('estim_weight') is-invalid @enderror" id="estim_weight" name="estim_weight" value="{{ old('estim_weight') }}">
                                    @error('estim_weight')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="estim_price" class="form-label">Estimated Price (optional)</label>
                                    <input type="number" step="0.01" min="0" class="form-control @error('estim_price') is-invalid @enderror" id="estim_price" name="estim_price" value="{{ old('estim_price') }}">
                                    <small id="estimPriceHint" class="text-muted d-block mt-1">Auto-calculated from selected subcategories and weight.</small>
                                    @error('estim_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="address" class="form-label">Customer Address</label>
                                    <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3" required>{{ old('address') }}</textarea>
                                    <small id="autofillDebug" class="text-muted d-block mt-1"></small>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="photos" class="form-label">Photos (up to 6)</label>
                                    <div id="photoDropZone" class="border rounded p-3 mb-2 text-center bg-light" style="cursor: pointer;">
                                        Drag and drop images here, or click to browse
                                    </div>
                                    <input type="file" class="form-control @error('photos') is-invalid @enderror @error('photos.*') is-invalid @enderror" id="photos" name="photos[]" accept="image/jpeg,image/jpg,image/png,image/webp" multiple>
                                    <small class="text-muted">Maximum 6 images, each up to 10MB.</small>
                                    <small id="photoCountHint" class="text-muted d-block mt-1"></small>
                                    @error('photos')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    @error('photos.*')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Create Order</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('contentjs')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const $ = window.jQuery;
    if (window.jQuery && typeof window.jQuery.fn.select2 === 'function') {
        window.jQuery('#user_id').select2({
            placeholder: 'Search user by name, phone, email, ID',
            allowClear: true,
            width: '100%'
        });
        window.jQuery('#category_ids').select2({
            placeholder: 'Select one or more categories',
            width: '100%'
        });
        window.jQuery('#subcategory_ids').select2({
            placeholder: 'Select one or more subcategories',
            width: '100%'
        });
    }

    const userSelect = document.getElementById('user_id');
    const categorySelect = document.getElementById('category_ids');
    const subcategorySelect = document.getElementById('subcategory_ids');
    const subcategorySearchInput = document.getElementById('subcategory_search');
    const subcategoryWeightsContainer = document.getElementById('subcategoryWeightsContainer');
    const addressInput = document.getElementById('address');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');
    const estimWeightInput = document.getElementById('estim_weight');
    const estimPriceInput = document.getElementById('estim_price');
    const estimPriceHint = document.getElementById('estimPriceHint');
    const photosInput = document.getElementById('photos');
    const photoDropZone = document.getElementById('photoDropZone');
    const photoCountHint = document.getElementById('photoCountHint');
    const autofillUrlTemplate = "{{ route('orders.userAutofill', ['id' => '__ID__']) }}";
    const autofillDebug = document.getElementById('autofillDebug');
    const oldSubcategoryValues = @json(array_map('strval', old('subcategory_ids', [])));
    const oldSubcategoryWeights = @json(old('subcategory_weights', []));

    function getSelectedCategoryIds() {
        if ($) {
            return ($('#category_ids').val() || []).map(String);
        }
        return Array.from(categorySelect.selectedOptions).map(function (opt) {
            return String(opt.value);
        });
    }

    function filterSubcategoriesByCategory() {
        const selectedCategoryIds = getSelectedCategoryIds();
        const searchTerm = (subcategorySearchInput.value || '').toLowerCase().trim();
        const options = Array.from(subcategorySelect.options);
        options.forEach(function (option, index) {
            const optionCategoryId = option.getAttribute('data-main-category-id') || '';
            const byCategory = selectedCategoryIds.length > 0 ? selectedCategoryIds.includes(String(optionCategoryId)) : true;
            const optionText = (option.text || '').toLowerCase();
            const bySearch = searchTerm ? optionText.includes(searchTerm) : true;
            option.hidden = !(byCategory && bySearch);
        });
        const selectedValues = $ ? ($('#subcategory_ids').val() || []).map(String) : Array.from(subcategorySelect.selectedOptions).map(function (opt) { return String(opt.value); });
        const validValues = selectedValues.filter(function (value) {
            const option = Array.from(subcategorySelect.options).find(function (opt) { return String(opt.value) === value; });
            return option && !option.hidden;
        });
        if ($) {
            $('#subcategory_ids').val(validValues).trigger('change.select2');
        }
        calculateEstimatedPrice();
    }

    function getSelectedSubcategoryOptions() {
        return Array.from(subcategorySelect.selectedOptions || []);
    }

    function getSubcategoryWeightsMap() {
        const map = {};
        const weightInputs = subcategoryWeightsContainer
            ? Array.from(subcategoryWeightsContainer.querySelectorAll('.subcategory-weight-input'))
            : [];
        weightInputs.forEach(function (input) {
            const subId = String(input.getAttribute('data-subcategory-id') || '');
            const val = (input.value || '').toString().trim();
            const num = parseFloat(val);
            if (subId && val !== '' && !isNaN(num) && num >= 0) {
                map[subId] = num;
            }
        });
        return map;
    }

    function syncTotalEstimatedWeight() {
        const weights = Object.values(getSubcategoryWeightsMap());
        if (weights.length === 0) {
            estimWeightInput.value = '';
            return;
        }
        const total = weights.reduce(function (sum, n) { return sum + n; }, 0);
        estimWeightInput.value = total.toFixed(2);
    }

    function renderSubcategoryWeightInputs() {
        if (!subcategoryWeightsContainer) {
            return;
        }
        const selectedOptions = getSelectedSubcategoryOptions();
        const existingValues = getSubcategoryWeightsMap();
        subcategoryWeightsContainer.innerHTML = '';

        selectedOptions.forEach(function (opt) {
            const subId = String(opt.value || '');
            if (!subId) {
                return;
            }
            const labelText = (opt.text || 'Subcategory').trim();
            const oldValue = oldSubcategoryWeights && Object.prototype.hasOwnProperty.call(oldSubcategoryWeights, subId)
                ? oldSubcategoryWeights[subId]
                : '';
            const value = Object.prototype.hasOwnProperty.call(existingValues, subId) ? existingValues[subId] : oldValue;

            const col = document.createElement('div');
            col.className = 'col-md-6';
            col.innerHTML = ''
                + '<label class="form-label mb-1">' + labelText + ' Weight</label>'
                + '<input '
                + 'type="number" step="0.01" min="0" '
                + 'class="form-control subcategory-weight-input" '
                + 'name="subcategory_weights[' + subId + ']" '
                + 'data-subcategory-id="' + subId + '" '
                + 'value="' + (value !== '' && value !== null && value !== undefined ? value : '') + '"'
                + '>';
            subcategoryWeightsContainer.appendChild(col);
        });

        syncTotalEstimatedWeight();
        calculateEstimatedPrice();
    }

    function calculateEstimatedPrice() {
        const selectedOptions = getSelectedSubcategoryOptions();
        const subcategoryWeightsMap = getSubcategoryWeightsMap();
        let weightedTotal = 0;

        selectedOptions.forEach(function (opt) {
            const subId = String(opt.value || '');
            const weight = parseFloat(subcategoryWeightsMap[subId] || '0');
            const defaultPrice = parseFloat(opt.getAttribute('data-default-price') || '0');
            if (weight > 0 && defaultPrice > 0) {
                weightedTotal += (weight * defaultPrice);
            }
        });

        if (weightedTotal > 0) {
            estimPriceInput.value = weightedTotal.toFixed(2);
            if (estimPriceHint) {
                estimPriceHint.textContent = 'Estimated Price = sum of (subcategory weight x default price).';
            }
            return;
        }

        const weight = parseFloat(estimWeightInput.value || '0');
        const defaultPrices = selectedOptions
            .map(function (opt) {
                return parseFloat(opt.getAttribute('data-default-price') || '0');
            })
            .filter(function (price) {
                return !isNaN(price) && price > 0;
            });

        if ((weight > 0) && defaultPrices.length > 0) {
            const avgPrice = defaultPrices.reduce(function (sum, p) { return sum + p; }, 0) / defaultPrices.length;
            const calculated = (weight * avgPrice).toFixed(2);
            estimPriceInput.value = calculated;
            if (estimPriceHint) {
                estimPriceHint.textContent = 'Estimated Price = Weight x Avg Default Price (' + avgPrice.toFixed(2) + ').';
            }
            return;
        }

        estimPriceInput.value = '';
        if (estimPriceHint) {
            estimPriceHint.textContent = 'Auto-calculated from selected subcategories and weight.';
        }
    }

    async function fillSelectedUserData() {
        const selectedUserId = userSelect.value || '';
        const selectedOption = userSelect.options[userSelect.selectedIndex];
        const defaultAddress = selectedOption ? (selectedOption.getAttribute('data-address') || '') : '';
        const savedLatitude = selectedOption ? (selectedOption.getAttribute('data-latitude') || '') : '';
        const savedLongitude = selectedOption ? (selectedOption.getAttribute('data-longitude') || '') : '';

        addressInput.value = defaultAddress;
        latitudeInput.value = savedLatitude;
        longitudeInput.value = savedLongitude;
        if (autofillDebug) {
            autofillDebug.textContent = selectedUserId ? 'Fetching saved address/coordinates...' : '';
        }

        if (!selectedUserId) {
            return;
        }

        try {
            const autofillUrl = autofillUrlTemplate.replace('__ID__', encodeURIComponent(selectedUserId));
            const response = await fetch(autofillUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();
            console.log('[CreateOrder][Autofill] URL:', autofillUrl);
            console.log('[CreateOrder][Autofill] Status:', response.status);
            console.log('[CreateOrder][Autofill] Result:', result);
            if (result && result.status === 'success' && result.data) {
                const fetchedAddress = (result.data.address || '').toString().trim();
                const fetchedLatitude = (result.data.latitude || '').toString().trim();
                const fetchedLongitude = (result.data.longitude || '').toString().trim();

                if (fetchedAddress) {
                    addressInput.value = fetchedAddress;
                }
                if (fetchedLatitude) {
                    latitudeInput.value = fetchedLatitude;
                }
                if (fetchedLongitude) {
                    longitudeInput.value = fetchedLongitude;
                }

                if (autofillDebug) {
                    autofillDebug.textContent = 'Autofill source response: address=' + (fetchedAddress ? 'yes' : 'no') + ', latitude=' + (fetchedLatitude ? fetchedLatitude : 'empty') + ', longitude=' + (fetchedLongitude ? fetchedLongitude : 'empty');
                }
            } else {
                if (autofillDebug) {
                    autofillDebug.textContent = 'Autofill API returned no data for this user.';
                }
            }
        } catch (e) {
            console.error('Failed to fetch user autofill data:', e);
            if (autofillDebug) {
                autofillDebug.textContent = 'Autofill request failed. Check browser console for error.';
            }
        }
    }

    userSelect.addEventListener('change', fillSelectedUserData);
    categorySelect.addEventListener('change', function () {
        filterSubcategoriesByCategory();
    });
    subcategorySearchInput.addEventListener('input', function () {
        filterSubcategoriesByCategory();
    });
    subcategorySelect.addEventListener('change', function () {
        renderSubcategoryWeightInputs();
        calculateEstimatedPrice();
    });
    estimWeightInput.addEventListener('input', function () {
        calculateEstimatedPrice();
    });
    if (subcategoryWeightsContainer) {
        subcategoryWeightsContainer.addEventListener('input', function (e) {
            if (e.target && e.target.classList.contains('subcategory-weight-input')) {
                syncTotalEstimatedWeight();
                calculateEstimatedPrice();
            }
        });
    }
    if ($) {
        $('#user_id').on('select2:select', function () {
            fillSelectedUserData();
        });
        $('#user_id').on('select2:clear', function () {
            addressInput.value = '';
            latitudeInput.value = '';
            longitudeInput.value = '';
            if (autofillDebug) {
                autofillDebug.textContent = '';
            }
        });
        $('#subcategory_ids').on('change', function () {
            renderSubcategoryWeightInputs();
            calculateEstimatedPrice();
        });
    }

    let selectedPhotoFiles = [];
    let syncingPhotosInput = false;

    function updatePhotoCountHint() {
        if (!photoCountHint) {
            return;
        }
        const count = selectedPhotoFiles.length;
        photoCountHint.textContent = count > 0 ? (count + ' image(s) selected.') : '';
    }

    function getFileKey(file) {
        return [file.name, file.size, file.lastModified].join('__');
    }

    function syncPhotosInput() {
        if (!photosInput) {
            return;
        }
        const dt = new DataTransfer();
        selectedPhotoFiles.forEach(function (file) {
            dt.items.add(file);
        });
        syncingPhotosInput = true;
        photosInput.files = dt.files;
        syncingPhotosInput = false;
        updatePhotoCountHint();
    }

    function addFiles(fileList) {
        if (!fileList) {
            return;
        }
        const incoming = Array.from(fileList).filter(function (file) {
            return /^image\//.test(file.type);
        });
        const merged = [];
        const seen = new Set();

        selectedPhotoFiles.concat(incoming).forEach(function (file) {
            const key = getFileKey(file);
            if (!seen.has(key)) {
                seen.add(key);
                merged.push(file);
            }
        });

        if (merged.length > 6) {
            alert('You can upload a maximum of 6 photos.');
        }

        selectedPhotoFiles = merged.slice(0, 6);
        syncPhotosInput();
    }

    photosInput.addEventListener('change', function () {
        if (syncingPhotosInput) {
            return;
        }
        addFiles(photosInput.files);
    });

    if (photoDropZone) {
        photoDropZone.addEventListener('click', function () {
            photosInput.click();
        });

        photoDropZone.addEventListener('dragover', function (e) {
            e.preventDefault();
            photoDropZone.classList.add('border-primary');
        });

        photoDropZone.addEventListener('dragleave', function () {
            photoDropZone.classList.remove('border-primary');
        });

        photoDropZone.addEventListener('drop', function (e) {
            e.preventDefault();
            photoDropZone.classList.remove('border-primary');
            addFiles(e.dataTransfer.files);
        });
    }

    // Auto-fill immediately if user is already selected (old input/edit-refresh case)
    filterSubcategoriesByCategory();
    if (oldSubcategoryValues.length > 0 && $) {
        $('#subcategory_ids').val(oldSubcategoryValues).trigger('change');
        filterSubcategoriesByCategory();
    }
    renderSubcategoryWeightInputs();
    calculateEstimatedPrice();
    updatePhotoCountHint();

    if (userSelect.value) {
        fillSelectedUserData();
    } else if (userSelect.options.length === 2 && userSelect.options[1].value) {
        // If only one real user option exists, auto-select and fetch
        userSelect.value = userSelect.options[1].value;
        if ($) {
            $('#user_id').trigger('change');
        } else {
            fillSelectedUserData();
        }
    }
});
</script>
@endsection
