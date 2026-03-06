@extends('index')
@section('content')

<div class="content-body">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Marketplace Posts</h4>
                        <p class="text-muted mb-0">Combined view of Bulk Sell and Bulk Buy posts with complete details</p>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="marketplacePostsTable" class="display table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Sl.No</th>
                                        <th>Post Type</th>
                                        <th>User</th>
                                        <th>Post Details</th>
                                        <th>Media / Docs</th>
                                        <th>Pricing</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="marketplacePostDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Marketplace Post Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modalReviewPostType">
                <input type="hidden" id="modalReviewPostId">
                <div id="marketplacePostDetailsView" style="max-height: 70vh; overflow:auto;"></div>
                <div class="form-group mt-3 mb-0">
                    <label class="font-weight-bold">Reject Reason</label>
                    <div class="mt-2">
                        <div class="form-check">
                            <input class="form-check-input marketplace-reason-radio" type="radio" name="marketplace_rejection_reason" id="marketReason1" value="Scrap type is missing or invalid">
                            <label class="form-check-label" for="marketReason1">Scrap type is missing or invalid</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input marketplace-reason-radio" type="radio" name="marketplace_rejection_reason" id="marketReason2" value="Category or subcategory selection is invalid">
                            <label class="form-check-label" for="marketReason2">Category or subcategory selection is invalid</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input marketplace-reason-radio" type="radio" name="marketplace_rejection_reason" id="marketReason3" value="Quantity details are invalid or incomplete">
                            <label class="form-check-label" for="marketReason3">Quantity details are invalid or incomplete</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input marketplace-reason-radio" type="radio" name="marketplace_rejection_reason" id="marketReason4" value="Price details are invalid or incomplete">
                            <label class="form-check-label" for="marketReason4">Price details are invalid or incomplete</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input marketplace-reason-radio" type="radio" name="marketplace_rejection_reason" id="marketReason5" value="Location or distance information is invalid">
                            <label class="form-check-label" for="marketReason5">Location or distance information is invalid</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input marketplace-reason-radio" type="radio" name="marketplace_rejection_reason" id="marketReason6" value="Images or video are missing or not clear">
                            <label class="form-check-label" for="marketReason6">Images or video are missing or not clear</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input marketplace-reason-radio" type="radio" name="marketplace_rejection_reason" id="marketReason7" value="Post description or notes are insufficient">
                            <label class="form-check-label" for="marketReason7">Post description or notes are insufficient</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input marketplace-reason-radio" type="radio" name="marketplace_rejection_reason" id="marketReason8" value="Duplicate or spam post">
                            <label class="form-check-label" for="marketReason8">Duplicate or spam post</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input marketplace-reason-radio" type="radio" name="marketplace_rejection_reason" id="marketReason9" value="Aadhar card document is improper">
                            <label class="form-check-label" for="marketReason9">Aadhar card document is improper</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input marketplace-reason-radio" type="radio" name="marketplace_rejection_reason" id="marketReason10" value="Driving license document is improper">
                            <label class="form-check-label" for="marketReason10">Driving license document is improper</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input marketplace-reason-radio" type="radio" name="marketplace_rejection_reason" id="marketReason11" value="Business license document is improper">
                            <label class="form-check-label" for="marketReason11">Business license document is improper</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input marketplace-reason-radio" type="radio" name="marketplace_rejection_reason" id="marketReason12" value="GST certificate document is improper">
                            <label class="form-check-label" for="marketReason12">GST certificate document is improper</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input marketplace-reason-radio" type="radio" name="marketplace_rejection_reason" id="marketReason13" value="Address proof document is improper">
                            <label class="form-check-label" for="marketReason13">Address proof document is improper</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input marketplace-reason-radio" type="radio" name="marketplace_rejection_reason" id="marketReason14" value="KYC owner document is improper">
                            <label class="form-check-label" for="marketReason14">KYC owner document is improper</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input marketplace-reason-radio" type="radio" name="marketplace_rejection_reason" id="marketReason15" value="Other">
                            <label class="form-check-label" for="marketReason15">Other</label>
                        </div>
                    </div>
                    <div class="mt-2" id="marketplaceOtherReasonWrap" style="display:none;">
                        <textarea id="marketplaceOtherReason" class="form-control" rows="3" placeholder="Enter rejection reason"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" onclick="submitModalReview('approve')">Approve</button>
                <button type="button" class="btn btn-warning" onclick="submitModalReview('pending')">Pending</button>
                <button type="button" class="btn btn-danger" onclick="submitModalReview('reject')">Reject</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('contentjs')
<script>
$(document).ready(function() {
    $('#marketplacePostsTable').DataTable({
        processing: true,
        serverSide: true,
        destroy: true,
        ajax: "{{ route('view_marketplacePosts') }}",
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'post_type_badge', name: 'post_type', orderable: false, searchable: true },
            { data: 'user_details', name: 'user_details', orderable: false },
            { data: 'post_details', name: 'post_details', orderable: false },
            { data: 'media_details', name: 'media_details', orderable: false },
            { data: 'pricing_details', name: 'pricing_details', orderable: false },
            { data: 'status', name: 'status' },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[7, 'desc']],
        pageLength: 25,
        language: {
            processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>'
        }
    });

    $(document).on('change', '.marketplace-reason-radio', function() {
        if ($(this).val() === 'Other') {
            $('#marketplaceOtherReasonWrap').show();
        } else {
            $('#marketplaceOtherReasonWrap').hide();
            $('#marketplaceOtherReason').val('');
        }
    });
});

function viewMarketplacePostDetails(payload) {
    try {
        const decoded = atob(payload);
        const post = JSON.parse(decoded);
        $('#modalReviewPostType').val(post.post_type || '');
        $('#modalReviewPostId').val(post.id || '');
        initializeMarketplaceRejectReason(post.review_reason || '');
        $('#marketplacePostDetailsView').html(renderPostDetails(post));
    } catch (e) {
        $('#marketplacePostDetailsView').html('<div class="alert alert-danger mb-0">Unable to parse details payload</div>');
    }
    $('#marketplacePostDetailsModal').modal('show');
}

function initializeMarketplaceRejectReason(reason) {
    $('.marketplace-reason-radio').prop('checked', false);
    $('#marketplaceOtherReasonWrap').hide();
    $('#marketplaceOtherReason').val('');
    if (!reason) return;

    const radios = $('.marketplace-reason-radio');
    let matched = false;
    radios.each(function() {
        if ($(this).val() === reason) {
            $(this).prop('checked', true);
            matched = true;
        }
    });
    if (!matched) {
        $('#marketReason15').prop('checked', true);
        $('#marketplaceOtherReasonWrap').show();
        $('#marketplaceOtherReason').val(reason);
    }
}

function safeJsonParse(value, fallback) {
    if (value === null || value === undefined) return fallback;
    if (typeof value === 'object') return value;
    try {
        const parsed = JSON.parse(value);
        return parsed ?? fallback;
    } catch (e) {
        return fallback;
    }
}

function esc(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function inr(value) {
    const n = Number(value);
    if (Number.isNaN(n)) return 'N/A';
    return '₹' + n.toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

function mediaFromPost(post) {
    const isUrl = (v) => typeof v === 'string' && /^https?:\/\//i.test(v);
    const documents = Array.isArray(post.documents) ? post.documents : [];
    const uploadedImages = Array.isArray(post.uploaded_images) ? post.uploaded_images : [];
    const images = [];
    const videos = [];

    [...uploadedImages, ...documents].forEach((url) => {
        if (!isUrl(url)) return;
        const lower = url.toLowerCase();
        if (lower.match(/\.(mp4|mov|m4v|webm)(\?|$)/)) {
            videos.push(url);
        } else if (lower.match(/\.(jpg|jpeg|png|webp|gif)(\?|$)/)) {
            images.push(url);
        }
    });

    return { images, videos, documents };
}

function renderSubcategoriesTable(subcategories, notesSubcategories) {
    const list = Array.isArray(subcategories) && subcategories.length > 0 ? subcategories : (Array.isArray(notesSubcategories) ? notesSubcategories : []);
    if (!Array.isArray(list) || list.length === 0) {
        return '<div class="text-muted">No subcategories</div>';
    }

    let rows = '';
    list.forEach((item, idx) => {
        const name = item.subcategory_name || item.name || 'Subcategory ' + (idx + 1);
        const qty = item.quantity ?? 'N/A';
        const price = item.asking_price ?? item.price ?? 'N/A';
        rows += `
            <tr>
                <td>${idx + 1}</td>
                <td>${esc(name)}</td>
                <td>${esc(qty)}</td>
                <td>${price === 'N/A' ? 'N/A' : inr(price)}</td>
            </tr>
        `;
    });

    return `
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Subcategory</th>
                        <th>Quantity</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
    `;
}

function renderMediaGrid(images, videos) {
    if (images.length === 0 && videos.length === 0) {
        return '<div class="text-muted">No media uploaded</div>';
    }

    let html = '<div class="row">';
    images.forEach((url, i) => {
        html += `
            <div class="col-md-4 mb-3">
                <div class="card">
                    <img src="${esc(url)}" class="card-img-top" style="height:140px;object-fit:cover;" alt="Image ${i + 1}">
                    <div class="card-body p-2 text-center">
                        <a href="${esc(url)}" target="_blank" class="btn btn-sm btn-outline-primary">View Image</a>
                    </div>
                </div>
            </div>
        `;
    });
    videos.forEach((url, i) => {
        html += `
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="d-flex align-items-center justify-content-center" style="height:140px;background:#f3f4f6;">
                        <i class="fa fa-play-circle fa-2x text-primary"></i>
                    </div>
                    <div class="card-body p-2 text-center">
                        <a href="${esc(url)}" target="_blank" class="btn btn-sm btn-outline-primary">Play Video ${i + 1}</a>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    return html;
}

function renderShopDocs(post) {
    const docs = post.shop_documents || {};
    const docList = [
        { key: 'aadhar_card', label: 'Aadhar Card' },
        { key: 'driving_license', label: 'Driving License' },
        { key: 'business_license_url', label: 'Business License' },
        { key: 'gst_certificate_url', label: 'GST Certificate' },
        { key: 'address_proof_url', label: 'Address Proof' },
        { key: 'kyc_owner_url', label: 'KYC Owner Doc' }
    ];
    const rows = docList
        .filter((d) => docs[d.key])
        .map((d) => `<li><a href="${esc(docs[d.key])}" target="_blank">${esc(d.label)}</a></li>`);
    if (rows.length === 0) {
        return '<div class="text-muted">No user KYC docs uploaded</div>';
    }
    return `<ul class="mb-0">${rows.join('')}</ul>`;
}

function renderProfile(post) {
    const profileImage = post.user_profile_image || null;
    const email = post.user_email || 'N/A';
    const userType = post.user_type || 'N/A';
    let imageHtml = '<div class="text-muted">No profile image</div>';
    if (profileImage) {
        imageHtml = `
            <img src="${esc(profileImage)}" alt="Profile" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:1px solid #ddd;">
            <div class="mt-1"><a href="${esc(profileImage)}" target="_blank">View profile image</a></div>
        `;
    }
    return `
        <div><strong>User Type:</strong> ${esc(userType)}</div>
        <div><strong>Email:</strong> ${esc(email)}</div>
        <div class="mt-2">${imageHtml}</div>
    `;
}

function renderPostDetails(post) {
    const postType = (post.post_type || '').toLowerCase() === 'sell' ? 'Bulk Sell Post' : 'Bulk Buy Post';
    const additional = safeJsonParse(post.additional_notes, {});
    const categories = Array.isArray(additional.categories) ? additional.categories.join(', ') : 'N/A';
    const note = additional.note || post.note || 'N/A';
    const condition = additional.condition || 'N/A';
    const pricingTerms = additional.pricingTerms || 'N/A';
    const quantity = post.quantity ?? 'N/A';
    const price = post.asking_price ?? post.preferred_price ?? post.price ?? null;
    const total = post.order_value ?? ((post.quantity && price) ? (Number(post.quantity) * Number(price)) : null);
    const sellerName = post.seller_name || post.shopname || post.username || 'N/A';
    const sellerId = post.seller_id || post.user_id || 'N/A';
    const userPhone = post.user_phone || post.phone || post.contact || 'N/A';
    const status = post.status || 'N/A';
    const paymentStatus = post.payment_status || 'N/A';
    const location = post.location || 'N/A';
    const distance = post.preferred_distance ? `${post.preferred_distance} km` : 'N/A';
    const whenAvailable = post.when_available || 'N/A';
    const { images, videos } = mediaFromPost(post);
    const subcategoriesHtml = renderSubcategoriesTable(post.subcategories, additional.subcategories);
    const mediaHtml = renderMediaGrid(images, videos);

    return `
        <div class="mb-3">
            <span class="badge badge-primary">${esc(postType)}</span>
            <span class="badge badge-secondary ml-1">Post ID: ${esc(post.id || 'N/A')}</span>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-header py-2"><strong>Seller / User</strong></div>
                    <div class="card-body py-2">
                        <div><strong>Name:</strong> ${esc(sellerName)}</div>
                        <div><strong>User ID:</strong> ${esc(sellerId)}</div>
                        <div><strong>Phone:</strong> ${esc(userPhone)}</div>
                        <div class="mt-2">${renderProfile(post)}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-header py-2"><strong>Price & Quantity</strong></div>
                    <div class="card-body py-2">
                        <div><strong>Quantity:</strong> ${esc(quantity)} kg</div>
                        <div><strong>Price:</strong> ${price === null ? 'N/A' : inr(price)}</div>
                        <div><strong>Total Value:</strong> ${total === null ? 'N/A' : inr(total)}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header py-2"><strong>Post Information</strong></div>
            <div class="card-body py-2">
                <div><strong>Scrap Type:</strong> ${esc(post.scrap_type || 'N/A')}</div>
                <div><strong>Location:</strong> ${esc(location)}</div>
                <div><strong>Preferred Distance:</strong> ${esc(distance)}</div>
                <div><strong>When Available:</strong> ${esc(whenAvailable)}</div>
                <div><strong>Status:</strong> ${esc(status)}</div>
                <div><strong>Payment Status:</strong> ${esc(paymentStatus)}</div>
                <div><strong>Created:</strong> ${esc(post.created_at || 'N/A')}</div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header py-2"><strong>Categories & Subcategories</strong></div>
            <div class="card-body py-2">
                <div class="mb-2"><strong>Categories:</strong> ${esc(categories)}</div>
                ${subcategoriesHtml}
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header py-2"><strong>Notes</strong></div>
            <div class="card-body py-2">
                <div><strong>Note:</strong> ${esc(note)}</div>
                <div><strong>Condition:</strong> ${esc(condition)}</div>
                <div><strong>Pricing Terms:</strong> ${esc(pricingTerms)}</div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header py-2"><strong>User Uploaded Documents</strong></div>
            <div class="card-body py-2">
                ${renderShopDocs(post)}
            </div>
        </div>

        <div class="card mb-0">
            <div class="card-header py-2"><strong>Uploaded Media</strong></div>
            <div class="card-body py-2">
                ${mediaHtml}
            </div>
        </div>
    `;
}

function updateMarketplacePostReview(postType, postId, action, reason = '') {
    if (!postType || !postId || !action) {
        notifyError('Unable to update: post details missing');
        return;
    }
    $.ajax({
        url: "{{ route('marketplacePostReview') }}",
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        data: {
            post_type: postType,
            post_id: postId,
            action: action,
            reason: reason,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                notifySuccess(response.message || 'Updated successfully');
                $('#marketplacePostsTable').DataTable().ajax.reload(null, false);
            } else {
                notifyError(response.message || 'Failed to update');
            }
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || xhr.responseJSON?.msg || 'Failed to update review';
            console.error('Marketplace review update failed:', {
                status: xhr.status,
                response: xhr.responseText
            });
            notifyError(errorMsg);
        }
    });
}

function submitModalReview(action) {
    const postType = $('#modalReviewPostType').val();
    const postId = $('#modalReviewPostId').val();
    let reason = '';
    if (!postType || !postId) {
        notifyError('Unable to continue: invalid post details');
        return;
    }
    if (action === 'reject' && !reason) {
        const selectedReason = $('input[name="marketplace_rejection_reason"]:checked').val();
        if (!selectedReason) {
            notifyError('Please select a rejection reason');
            return;
        }
        if (selectedReason === 'Other') {
            reason = ($('#marketplaceOtherReason').val() || '').trim();
            if (!reason) {
                notifyError('Please specify the rejection reason');
                return;
            }
        } else {
            reason = selectedReason;
        }
    }
    updateMarketplacePostReview(postType, postId, action, reason);
}

function notifySuccess(msg) {
    if (window.toastr && typeof window.toastr.success === 'function') {
        window.toastr.success(msg);
        return;
    }
    alert(msg);
}

function notifyError(msg) {
    if (window.toastr && typeof window.toastr.error === 'function') {
        window.toastr.error(msg);
        return;
    }
    alert(msg);
}
</script>
@endsection
