@extends('index')
@section('content')

<div class="content-body">
    <div class="container-fluid">
        <style>
            .tender-page-wrap {
                background: #eef2f7;
                padding: 8px;
                border-radius: 8px;
            }
            .tender-detail-card {
                background: #fff;
                border: 1px solid #d9e1ee;
                border-radius: 6px;
                margin-bottom: 14px;
                overflow: hidden;
            }
            .tender-panel-title {
                background: #cfd6e2;
                color: #1d3b66;
                font-weight: 700;
                padding: 10px 14px;
                font-size: 24px;
            }
            .tender-panel-subtitle {
                color: #5f7190;
                padding: 6px 14px 12px;
                font-size: 14px;
            }
            .tender-main-block {
                margin: 0 10px 10px;
                border: 1px solid #dce4f0;
                border-radius: 4px;
                padding: 12px 14px;
            }
            .tender-main-link {
                display: block;
                color: inherit;
                text-decoration: none;
            }
            .tender-main-link:hover {
                color: inherit;
                text-decoration: none;
            }
            .tender-main-link .tender-main-block {
                transition: box-shadow .2s ease, transform .2s ease;
                cursor: pointer;
            }
            .tender-main-link:hover .tender-main-block {
                box-shadow: 0 6px 16px rgba(27, 64, 109, 0.12);
                transform: translateY(-1px);
            }
            .tender-main-title {
                color: #143764;
                font-weight: 700;
                margin-bottom: 10px;
            }
            .tender-chip {
                display: inline-block;
                background: #edf2f8;
                color: #2a4d7b;
                border-radius: 20px;
                padding: 4px 12px;
                font-size: 14px;
                margin-right: 6px;
                margin-bottom: 8px;
            }
            .tender-location {
                color: #2b5d98;
                margin-bottom: 8px;
            }
            .tender-stat-title {
                color: #5d7191;
                font-size: 14px;
            }
            .tender-stat-value {
                color: #173a66;
                font-size: 32px;
                line-height: 1.1;
                font-weight: 700;
            }
            .tender-stat-value.warn {
                color: #d07d22;
            }
            .tender-tabs {
                margin: 0 10px 10px;
                padding-bottom: 4px;
            }
            .tender-tab {
                display: inline-block;
                border: 1px solid #cfd9e7;
                color: #2c5ca0;
                border-radius: 999px;
                padding: 7px 20px;
                margin-right: 8px;
                margin-bottom: 8px;
                background: #fff;
                font-size: 15px;
            }
            .tender-tab.active {
                background: linear-gradient(90deg, #35c9bb 0%, #4a8ddb 100%);
                color: #fff;
                border-color: transparent;
            }
            .section-head {
                background: #cfd6e2;
                color: #1e3a65;
                font-weight: 700;
                padding: 10px 14px;
                font-size: 28px;
                line-height: 1.2;
            }
            .section-body {
                padding: 14px;
                color: #1f3e67;
            }
            .kv-row {
                display: flex;
                gap: 20px;
                margin-bottom: 8px;
                flex-wrap: wrap;
            }
            .kv-key {
                width: 230px;
                color: #577096;
            }
            .kv-val {
                flex: 1;
                min-width: 220px;
                color: #173a66;
                word-break: break-word;
            }
            .docs-toolbar {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                margin-bottom: 10px;
            }
            .docs-btn {
                border: 1px solid #2b66d9;
                background: #2b66d9;
                color: #fff;
                border-radius: 6px;
                padding: 8px 14px;
                font-weight: 600;
            }
            .doc-row {
                border-top: 1px solid #d8e2ef;
                padding: 12px 0;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
            }
            .doc-name {
                color: #2b66d9;
                font-size: 24px;
                line-height: 1.2;
            }
            .doc-meta {
                color: #5f7190;
                font-size: 16px;
            }
            .doc-download {
                color: #2b66d9;
                font-size: 26px;
                text-decoration: none;
            }
            .doc-download.disabled {
                color: #9aa8bf;
                cursor: not-allowed;
                pointer-events: none;
            }
            .tender-summary-actions {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                padding: 0 10px 12px;
            }
            .tender-summary-btn {
                border: 1px solid #2b66d9;
                color: #2b66d9;
                border-radius: 8px;
                padding: 8px 16px;
                font-weight: 600;
                background: #fff;
            }
            .tender-summary-btn.primary {
                background: #2b66d9;
                color: #fff;
            }
            @media (max-width: 768px) {
                .tender-panel-title { font-size: 18px; }
                .section-head { font-size: 20px; }
                .tender-stat-value { font-size: 22px; }
                .doc-name { font-size: 18px; }
                .doc-meta { font-size: 14px; }
                .kv-key { width: 100%; margin-bottom: 2px; }
                .kv-val { min-width: 100%; }
                .kv-row { gap: 4px; margin-bottom: 12px; }
            }
        </style>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h4 class="card-title mb-1">{{ $pagename ?? 'Tenders' }}</h4>
                            </div>
                        </div>

                        @include('layouts.flashmessage')

                        <form method="GET" action="{{ request()->routeIs('tenders.v2') ? route('tenders.v2') : route('tenders.index') }}" class="mb-3">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label mb-1">State</label>
                                    <select name="state" class="form-control">
                                        <option value="">All States</option>
                                        @foreach(($states ?? []) as $state)
                                            <option value="{{ $state }}" {{ ($selectedState ?? '') === $state ? 'selected' : '' }}>{{ $state }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label mb-1">Bidassist Auth Token</label>
                                    <input
                                        type="text"
                                        name="bidassist_auth_token"
                                        class="form-control"
                                        value="{{ $bidassistAuthToken ?? '' }}"
                                        placeholder="Paste BIDASSIST_AUTH_TOKEN"
                                    >
                                </div>
                                @if(request()->routeIs('tenders.v2'))
                                    <div class="col-md-1">
                                        <label class="form-label mb-1">Page</label>
                                        <input
                                            type="number"
                                            min="0"
                                            name="scrape_page"
                                            class="form-control"
                                            value="{{ request()->query('scrape_page', 0) }}"
                                        >
                                    </div>
                                @endif
                                @if(!request()->routeIs('tenders.v2'))
                                    <div class="col-md-2">
                                        <label class="form-label mb-1">Sort</label>
                                        <select name="sort" class="form-control">
                                            <option value="state_asc" {{ ($sortBy ?? 'state_asc') === 'state_asc' ? 'selected' : '' }}>State A-Z</option>
                                            <option value="state_desc" {{ ($sortBy ?? '') === 'state_desc' ? 'selected' : '' }}>State Z-A</option>
                                            <option value="title_asc" {{ ($sortBy ?? '') === 'title_asc' ? 'selected' : '' }}>Title A-Z</option>
                                            <option value="title_desc" {{ ($sortBy ?? '') === 'title_desc' ? 'selected' : '' }}>Title Z-A</option>
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label mb-1">Per Page</label>
                                        <select name="per_page" class="form-control">
                                            @foreach([5,10,20,50] as $pp)
                                                <option value="{{ $pp }}" {{ (int)($perPage ?? 5) === $pp ? 'selected' : '' }}>{{ $pp }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                @if(request()->routeIs('tenders.v2'))
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-outline-primary w-100">Apply</button>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" name="sync" value="1" class="btn btn-primary w-100">Apply and Continue</button>
                                    </div>
                                @else
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">Apply</button>
                                    </div>
                                @endif
                            </div>
                        </form>
                        @if(request()->routeIs('tenders.v2'))
                            <form method="POST" action="{{ route('tenders.v2.fixSaved') }}" class="mb-3">
                                @csrf
                                <input type="hidden" name="state" value="{{ $selectedState ?? '' }}">
                                <input type="hidden" name="bidassist_auth_token" value="{{ $bidassistAuthToken ?? '' }}">
                                <button type="submit" class="btn btn-warning">Fix All Saved Docs & URLs</button>
                            </form>
                        @endif
                        <input type="hidden" id="bidassistAuthToken" value="{{ $bidassistAuthToken ?? '' }}">

                        @if(isset($tenders) && $tenders->count() > 0)
                            <div class="tender-page-wrap">
                            @foreach($tenders as $tender)
                                @php
                                    $title = trim((string) ($tender['title'] ?? '-'));
                                    $type = trim((string) ($tender['type'] ?? ''));
                                    $category = trim((string) ($tender['category'] ?? ''));
                                    $platform = trim((string) ($tender['platform'] ?? ''));
                                    $keyword = trim((string) ($tender['keyword'] ?? ''));
                                    $services = trim((string) ($tender['services'] ?? ''));
                                    $pricing = trim((string) ($tender['pricing'] ?? ($tender['tender_value'] ?? '')));
                                    $phone = trim((string) ($tender['phone_number'] ?? ''));
                                    $location = trim((string) ($tender['location'] ?? ''));
                                    $description = trim((string) ($tender['description'] ?? ''));
                                    $openingDate = trim((string) ($tender['opening_date'] ?? ''));
                                    $closingDate = trim((string) ($tender['closing_date'] ?? ''));
                                    $closingLabel = trim((string) ($tender['closing_label'] ?? 'Closing Date'));
                                    $tenderAmount = trim((string) ($tender['tender_value'] ?? ''));
                                    $emd = trim((string) ($tender['emd'] ?? ''));
                                    $tenderId = trim((string) ($tender['tender_id'] ?? ''));
                                    $tenderNo = trim((string) ($tender['tender_no'] ?? ''));
                                    $url = trim((string) ($tender['url'] ?? ''));
                                    $detailSourceUrl = trim((string) (($tender['raw']['source_url'] ?? '') ?: ($tender['raw']['url'] ?? '') ?: $url));
                                    $documents = (is_array($tender['documents'] ?? null)) ? $tender['documents'] : [];

                                    $isNA = function ($value) {
                                        $v = strtolower(trim((string) $value));
                                        return $v === '' || $v === 'n/a' || $v === 'na' || $v === '-';
                                    };

                                    if ($isNA($platform)) $platform = '';
                                    if ($isNA($keyword)) $keyword = '';
                                    if ($isNA($services)) $services = '';
                                    if ($isNA($phone)) $phone = '';
                                    $summary = trim((string) (($tender['raw']['summary'] ?? '') ?: $description));
                                    $authority = trim((string) ($tender['authority'] ?? ''));
                                    $tenderAuthority = trim((string) (($tender['raw']['tender_authority'] ?? '') ?: $authority));
                                    $purchaserAddress = trim((string) ($tender['raw']['purchaser_address'] ?? ''));
                                    $website = trim((string) ($tender['raw']['website'] ?? ''));
                                    if ($website === '' && $url !== '') {
                                        $website = $url;
                                    }
                                    $majorSourceData = is_array($tender['major_source_data'] ?? null) ? $tender['major_source_data'] : [];
                                    $majorLocation = is_array($majorSourceData['5_location_information'] ?? null) ? $majorSourceData['5_location_information'] : [];
                                    $majorPurchaser = is_array($majorSourceData['4_purchaser_authority_information'] ?? null) ? $majorSourceData['4_purchaser_authority_information'] : [];
                                    $majorSourceJson = !empty($majorSourceData) ? json_encode($majorSourceData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '{}';
                                    $modalId = 'tenderDetailsModal_' . ($tender['sl_no'] ?? $loop->index ?? rand(1, 9999));
                                @endphp
                                <div class="tender-detail-card">
                                    <div class="tender-panel-title">{{ $title }}</div>
                                    <div class="tender-panel-subtitle">
                                        {{ $description !== '' ? $description : 'Tender details fetched from saved records.' }}
                                    </div>

                                    <div
                                        class="tender-main-link"
                                        role="button"
                                        data-toggle="modal"
                                        data-target="#{{ $modalId }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#{{ $modalId }}"
                                    >
                                        <div class="tender-main-block">
                                            <div class="tender-main-title">{{ $title }}</div>
                                            <div class="mb-2">
                                                @if(!$isNA($type))<span class="tender-chip">{{ $type }}</span>@endif
                                                @if(!$isNA($category))<span class="tender-chip">{{ $category }}</span>@endif
                                                @if(!$isNA($platform))<span class="tender-chip">{{ $platform }}</span>@endif
                                            </div>
                                            <div class="tender-location">
                                                <i class="fa fa-map-marker me-1"></i>{{ $location !== '' ? $location : 'Kerala' }}
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-md-4">
                                                    <div class="tender-stat-title">Opening Date</div>
                                                    <div class="tender-stat-value">{{ $openingDate !== '' ? $openingDate : '-' }}</div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="tender-stat-title">{{ $closingLabel !== '' ? $closingLabel : 'Closing Date' }}</div>
                                                    <div class="tender-stat-value warn">{{ $closingDate !== '' ? $closingDate : '-' }}</div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="tender-stat-title">Tender Amount</div>
                                                    <div class="tender-stat-value">{{ $pricing !== '' ? $pricing : '-' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tender-summary-actions">
                                        <button type="button" class="tender-summary-btn">Follow</button>
                                        <button
                                            type="button"
                                            class="tender-summary-btn primary"
                                            data-toggle="modal"
                                            data-target="#{{ $modalId }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#{{ $modalId }}"
                                        >Download</button>
                                    </div>
                                </div>

                                <div
                                    class="modal fade js-tender-modal"
                                    id="{{ $modalId }}"
                                    tabindex="-1"
                                    role="dialog"
                                    aria-labelledby="{{ $modalId }}Label"
                                    aria-hidden="true"
                                    data-detail-url="{{ $detailSourceUrl }}"
                                    data-title="{{ $title }}"
                                    data-authority="{{ $authority }}"
                                    data-location="{{ $location }}"
                                    data-tender-id="{{ $tenderId !== '' ? $tenderId : $tenderNo }}"
                                    data-doc-count="{{ count($documents) }}"
                                >
                                    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="{{ $modalId }}Label">{{ $title }}</h5>
                                                <input
                                                    type="text"
                                                    class="form-control form-control-sm js-modal-token me-2"
                                                    data-modal-id="{{ $modalId }}"
                                                    style="max-width: 320px;"
                                                    placeholder="Paste BIDASSIST_AUTH_TOKEN"
                                                    value="{{ $bidassistAuthToken ?? '' }}"
                                                >
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-primary js-refresh-detail"
                                                    data-modal-id="{{ $modalId }}"
                                                >
                                                    Add/Refresh New Data
                                                </button>
                                                <button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body p-0">
                                                <div class="tender-tabs mt-2">
                                                    <span class="tender-tab active">Overview</span>
                                                    <span class="tender-tab">Documents</span>
                                                    <span class="tender-tab">BOQ</span>
                                                    <span class="tender-tab">Notes</span>
                                                    <span class="tender-tab">Potential Partner</span>
                                                    <span class="tender-tab">Related Tenders</span>
                                                </div>

                                                <div class="section-head">Costs</div>
                                                <div class="section-body">
                                                    <div class="kv-row"><div class="kv-key">EMD</div><div class="kv-val" id="{{ $modalId }}_emd">{{ $emd !== '' ? $emd : 'Refer Documents' }}</div></div>
                                                    <div class="kv-row"><div class="kv-key">Document Cost</div><div class="kv-val" id="{{ $modalId }}_document_cost">{{ $tender['raw']['document_cost'] ?? ($tender['raw']['documentCost'] ?? '-') }}</div></div>
                                                    <div class="kv-row"><div class="kv-key">Tender Fee</div><div class="kv-val" id="{{ $modalId }}_tender_fee">{{ $tender['raw']['tender_fee'] ?? ($tender['raw']['tenderFee'] ?? '-') }}</div></div>
                                                </div>

                                                <div class="section-head">Summary</div>
                                                <div class="section-body">{{ $summary !== '' ? $summary : '-' }}</div>

                                                <div class="section-head">Description</div>
                                                <div class="section-body">{{ $description !== '' ? $description : '-' }}</div>

                                                <div class="section-head">Contact</div>
                                                <div class="section-body">
                                                    <div class="kv-row"><div class="kv-key">Tender Id</div><div class="kv-val" id="{{ $modalId }}_tender_id">{{ $tenderId !== '' ? $tenderId : '-' }}</div></div>
                                                    <div class="kv-row"><div class="kv-key">Tender No</div><div class="kv-val" id="{{ $modalId }}_tender_no">{{ $tenderNo !== '' ? $tenderNo : '-' }}</div></div>
                                                    <div class="kv-row"><div class="kv-key">Tender Authority</div><div class="kv-val" id="{{ $modalId }}_tender_authority">{{ $tenderAuthority !== '' ? $tenderAuthority : '-' }}</div></div>
                                                    <div class="kv-row"><div class="kv-key">Purchaser Address</div><div class="kv-val" id="{{ $modalId }}_purchaser_address">{{ $purchaserAddress !== '' ? $purchaserAddress : '-' }}</div></div>
                                                    <div class="kv-row"><div class="kv-key">Email</div><div class="kv-val" id="{{ $modalId }}_email">{{ trim((string) ($majorPurchaser['purchaserEmail'] ?? '')) !== '' ? $majorPurchaser['purchaserEmail'] : '-' }}</div></div>
                                                    <div class="kv-row">
                                                        <div class="kv-key">Website</div>
                                                        <div class="kv-val" id="{{ $modalId }}_website_wrap">
                                                            @if($website !== '')
                                                                <a id="{{ $modalId }}_website" href="{{ $website }}" target="_blank">{{ $website }}</a>
                                                            @else
                                                                <span id="{{ $modalId }}_website">-</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="kv-row">
                                                        <div class="kv-key">Tender URL</div>
                                                        <div class="kv-val" id="{{ $modalId }}_tender_url_wrap">
                                                            @if($url !== '')
                                                                <a id="{{ $modalId }}_tender_url" href="{{ $url }}" target="_blank">{{ $url }}</a>
                                                            @else
                                                                <span id="{{ $modalId }}_tender_url">-</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="kv-row"><div class="kv-key">Location</div><div class="kv-val" id="{{ $modalId }}_loc_location">{{ trim((string) ($majorLocation['location'] ?? '')) !== '' ? $majorLocation['location'] : ($location !== '' ? $location : '-') }}</div></div>
                                                    <div class="kv-row"><div class="kv-key">District</div><div class="kv-val" id="{{ $modalId }}_loc_district">{{ trim((string) ($majorLocation['district'] ?? '')) !== '' ? $majorLocation['district'] : '-' }}</div></div>
                                                    <div class="kv-row"><div class="kv-key">State</div><div class="kv-val" id="{{ $modalId }}_loc_state">{{ trim((string) ($majorLocation['state'] ?? '')) !== '' ? $majorLocation['state'] : '-' }}</div></div>
                                                    <div class="kv-row"><div class="kv-key">Taluk</div><div class="kv-val" id="{{ $modalId }}_loc_taluk">{{ trim((string) ($majorLocation['taluk'] ?? '')) !== '' ? $majorLocation['taluk'] : '-' }}</div></div>
                                                    <div class="kv-row"><div class="kv-key">Pincode</div><div class="kv-val" id="{{ $modalId }}_loc_pincode">{{ trim((string) ($majorLocation['pincode'] ?? '')) !== '' ? $majorLocation['pincode'] : '-' }}</div></div>
                                                    <div class="kv-row"><div class="kv-key">Zone</div><div class="kv-val" id="{{ $modalId }}_loc_zone">{{ trim((string) ($majorLocation['zone'] ?? '')) !== '' ? $majorLocation['zone'] : '-' }}</div></div>
                                                    <div class="kv-row"><div class="kv-key">Country</div><div class="kv-val" id="{{ $modalId }}_loc_country">{{ trim((string) ($majorLocation['country'] ?? '')) !== '' ? $majorLocation['country'] : 'India' }}</div></div>
                                                    <div class="kv-row"><div class="kv-key">Pincode Detail ID</div><div class="kv-val" id="{{ $modalId }}_loc_pincode_detail_id">{{ trim((string) ($majorLocation['pincodeDetailId'] ?? '')) !== '' ? $majorLocation['pincodeDetailId'] : '-' }}</div></div>
                                                    <div class="kv-row"><div class="kv-key">Location Source</div><div class="kv-val" id="{{ $modalId }}_loc_location_source">{{ trim((string) ($majorLocation['locationSource'] ?? '')) !== '' ? $majorLocation['locationSource'] : '-' }}</div></div>
                                                </div>

                                                <div class="section-head">Major Source Data</div>
                                                <div class="section-body">
                                                    <div id="{{ $modalId }}_major_data" class="js-major-data"><pre class="mb-0" style="white-space: pre-wrap; max-height: 320px; overflow: auto;">{{ $majorSourceJson }}</pre></div>
                                                </div>

                                                <div class="section-head">Documents</div>
                                                <div class="section-body">
                                                    <div class="docs-toolbar">
                                                        <button type="button" class="docs-btn">View Pricing</button>
                                                        <button type="button" class="docs-btn">Download All</button>
                                                    </div>
                                                    <div id="{{ $modalId }}_docs_list" class="js-docs-list">
                                                        @if(!empty($documents))
                                                            @foreach($documents as $doc)
                                                                @php
                                                                    $docLink = trim((string) ((($doc['s3_url'] ?? '') ?: ($doc['doc_url'] ?? '') ?: ($doc['docUrl'] ?? ''))));
                                                                    $docLinkLower = strtolower($docLink);
                                                                    if ($docLink !== '' && !str_starts_with($docLinkLower, 'http://') && !str_starts_with($docLinkLower, 'https://')) {
                                                                        $docLink = '';
                                                                    }
                                                                    $docLabel = $doc['doc_label'] ?? '';
                                                                    $docName = $doc['file_name'] ?? ($docLabel !== '' ? $docLabel : 'Document');
                                                                    $docSize = $doc['file_size'] ?? '';
                                                                    $downloadHref = $docLink !== '' ? $docLink : '#';
                                                                    $isDirectDownload = $docLink !== '';
                                                                @endphp
                                                                <div class="doc-row">
                                                                    <div>
                                                                        <div class="doc-name">{{ $docLabel !== '' ? $docLabel : $docName }}</div>
                                                                        <div class="doc-meta">
                                                                            {{ $docSize !== '' ? $docSize . ' ' : '' }}{{ $docName }}
                                                                        </div>
                                                                    </div>
                                                                    <a
                                                                        class="doc-download {{ $downloadHref === '#' ? 'disabled' : '' }}"
                                                                        href="{{ $downloadHref }}"
                                                                        target="_blank"
                                                                        title="{{ $isDirectDownload ? 'Download' : 'Unavailable' }}"
                                                                    >
                                                                        <i class="fa fa-download"></i>
                                                                    </a>
                                                                </div>
                                                            @endforeach
                                                        @else
                                                            <div class="doc-row js-no-doc-row">
                                                                <div class="doc-meta">No documents available.</div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            </div>
                            <div class="mt-3 d-flex justify-content-center">
                                {{ $tenders->links('pagination::bootstrap-5') }}
                            </div>
                        @else
                            <div class="alert alert-info mb-0">
                                No tenders found for selected state/sort.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const refreshUrl = @json(route('tenders.refreshDetail'));
        const refreshV2Url = @json(route('tenders.v2.refreshDocs'));
        const csrf = @json(csrf_token());
        const modalSelector = '.js-tender-modal';
        const isTenderV2 = @json(request()->routeIs('tenders.v2'));

        function esc(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function setText(id, value) {
            const node = document.getElementById(id);
            if (!node) return;
            node.textContent = (value && String(value).trim() !== '') ? value : '-';
        }

        function setLink(wrapId, linkId, value, label) {
            const wrap = document.getElementById(wrapId);
            if (!wrap) return;
            const v = (value || '').trim();
            if (!v) {
                wrap.innerHTML = `<span id="${linkId}">-</span>`;
                return;
            }
            wrap.innerHTML = `<a id="${linkId}" href="${esc(v)}" target="_blank">${esc(label)}</a>`;
        }

        function renderDocs(listId, docs, statusLines = []) {
            const list = document.getElementById(listId);
            if (!list) return;

            const statusHtml = (Array.isArray(statusLines) ? statusLines : [])
                .filter(Boolean)
                .map((s) => `<div class="doc-row"><div class="doc-meta">${esc(s)}</div></div>`)
                .join('');

            if (!Array.isArray(docs) || docs.length === 0) {
                list.innerHTML = statusHtml + '<div class="doc-row js-no-doc-row"><div class="doc-meta">No documents available.</div></div>';
                return;
            }

            const rows = docs.map((doc) => {
                const label = (doc.doc_label || '').trim();
                const fileName = ((doc.file_name || '').trim() || label || 'Document');
                const size = (doc.file_size || '').trim();
                let href = (doc.s3_url || doc.doc_url || doc.docUrl || '').trim();
                const hrefLower = href.toLowerCase();
                if (href && !(hrefLower.startsWith('http://') || hrefLower.startsWith('https://'))) {
                    href = '';
                }
                const disabled = href ? '' : ' disabled';
                const title = href ? 'Download' : 'Unavailable';
                return `
                    <div class="doc-row">
                        <div>
                            <div class="doc-name">${esc(label || fileName)}</div>
                            <div class="doc-meta">${esc((size ? size + ' ' : '') + fileName)}</div>
                        </div>
                        <a class="doc-download${disabled}" href="${esc(href || '#')}" target="_blank" title="${esc(title)}">
                            <i class="fa fa-download"></i>
                        </a>
                    </div>
                `;
            });
            list.innerHTML = statusHtml + rows.join('');
        }

        function renderMajorSourceData(containerId, payload) {
            const box = document.getElementById(containerId);
            if (!box) return;
            const data = (payload && typeof payload === 'object') ? payload : {};
            let pretty = '{}';
            try {
                pretty = JSON.stringify(data, null, 2) || '{}';
            } catch (_) {
                pretty = '{}';
            }
            box.innerHTML = `<pre class="mb-0" style="white-space: pre-wrap; max-height: 320px; overflow: auto;">${esc(pretty)}</pre>`;
        }

        function renderLocationFromMajor(modalId, payload, fallbackLocation) {
            const major = (payload && payload['5_location_information'] && typeof payload['5_location_information'] === 'object')
                ? payload['5_location_information']
                : {};
            setText(modalId + '_loc_location', major.location || fallbackLocation || '');
            setText(modalId + '_loc_district', major.district || '');
            setText(modalId + '_loc_state', major.state || '');
            setText(modalId + '_loc_taluk', major.taluk || '');
            setText(modalId + '_loc_pincode', major.pincode || '');
            setText(modalId + '_loc_zone', major.zone || '');
            setText(modalId + '_loc_country', major.country || 'India');
            setText(modalId + '_loc_pincode_detail_id', major.pincodeDetailId || '');
            setText(modalId + '_loc_location_source', major.locationSource || '');
        }

        function renderPurchaserFromMajor(modalId, payload) {
            const purchaser = (payload && payload['4_purchaser_authority_information'] && typeof payload['4_purchaser_authority_information'] === 'object')
                ? payload['4_purchaser_authority_information']
                : {};
            setText(modalId + '_email', purchaser.purchaserEmail || '');
        }

        function modalHasDownloadableDocs(modal) {
            if (!modal) return false;
            const list = document.getElementById(modal.id + '_docs_list');
            if (!list) return false;
            const links = list.querySelectorAll('.doc-download[href]');
            for (const link of links) {
                const href = (link.getAttribute('href') || '').trim();
                const lower = href.toLowerCase();
                const isHttp = lower.startsWith('http://') || lower.startsWith('https://');
                if (!href || href === '#' || link.classList.contains('disabled') || !isHttp) continue;
                return true;
            }
            return false;
        }

        async function refreshModalDetail(modal, force = false) {
            if (!modal || modal.dataset.refreshing === '1') return;
            if (isTenderV2 && !force) return;

            const detailUrl = (modal.dataset.detailUrl || '').trim();
            const tenderId = (modal.dataset.tenderId || '').trim();
            const modalTokenInput = modal.querySelector('.js-modal-token');
            const modalToken = modalTokenInput ? String(modalTokenInput.value || '').trim() : '';
            const pageTokenInput = document.getElementById('bidassistAuthToken');
            const pageToken = pageTokenInput ? String(pageTokenInput.value || '').trim() : '';
            const effectiveToken = modalToken || pageToken;
            if (!isTenderV2 && (!detailUrl || detailUrl.indexOf('/detail-') === -1)) return;
            if (isTenderV2 && !tenderId) return;
            if (!force && modalHasDownloadableDocs(modal)) return;
            if (isTenderV2 && !effectiveToken) {
                renderDocs(modal.id + '_docs_list', [], [
                    'BIDASSIST auth token is required.',
                    'Paste token in modal input and click Add/Refresh New Data.'
                ]);
                return;
            }

            const listId = modal.id + '_docs_list';
            const list = document.getElementById(listId);
            renderDocs(listId, [], ['Starting re-scrape...', 'Sending request to backend...']);

            modal.dataset.refreshing = '1';
            try {
                const controller = new AbortController();
                const timeoutMs = 180000; // Detail scrape + S3 upload can take longer for larger docs.
                const timeout = setTimeout(() => controller.abort('refresh-timeout'), timeoutMs);
                const res = await fetch(isTenderV2 ? refreshV2Url : refreshUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                    signal: controller.signal,
                    body: JSON.stringify({
                        detail_url: detailUrl,
                        tender_id: tenderId,
                        title: modal.dataset.title || '',
                        authority: modal.dataset.authority || '',
                        location: modal.dataset.location || '',
                        bidassist_auth_token: effectiveToken
                    })
                });
                clearTimeout(timeout);
                const payload = await res.json();
                if (!res.ok || payload.status !== 'success' || !payload.data) {
                    const failTrace = (payload && payload.meta && Array.isArray(payload.meta.trace)) ? payload.meta.trace : [];
                    throw new Error([(payload && payload.msg) ? payload.msg : 'Refresh failed', ...failTrace].filter(Boolean).join(' | '));
                }

                const d = payload.data;
                setText(modal.id + '_tender_id', d.tender_id);
                setText(modal.id + '_tender_no', d.tender_no);
                setText(modal.id + '_tender_authority', d.tender_authority);
                setText(modal.id + '_purchaser_address', d.purchaser_address);
                setText(modal.id + '_emd', d.emd);
                setText(modal.id + '_document_cost', d.document_cost);
                setText(modal.id + '_tender_fee', d.tender_fee);
                setLink(modal.id + '_website_wrap', modal.id + '_website', d.website, d.website || 'Website');
                setLink(modal.id + '_tender_url_wrap', modal.id + '_tender_url', d.tender_url, d.tender_url || 'Tender URL');
                renderMajorSourceData(modal.id + '_major_data', d.major_source_data || {});
                renderLocationFromMajor(modal.id, d.major_source_data || {}, modal.dataset.location || '');
                renderPurchaserFromMajor(modal.id, d.major_source_data || {});
                const trace = (payload.meta && Array.isArray(payload.meta.trace)) ? payload.meta.trace : [];
                const source = (payload.meta && payload.meta.source) ? payload.meta.source : 'unknown';
                renderDocs(listId, d.documents || [], [`Re-scrape completed. Source: ${source}`, ...trace]);
                modal.dataset.docCount = String(Number(d.documents_count || 0));
            } catch (err) {
                const isAbort = err && err.name === 'AbortError';
                const msg = isAbort
                    ? 'Re-scrape timed out after 180s. Please reopen the modal to retry.'
                    : (err && err.message ? err.message : 'Re-scrape failed. Try opening again.');
                renderDocs(listId, [], [isTenderV2 ? 'Refresh failed.' : 'Re-scrape failed.', msg]);
            } finally {
                modal.dataset.refreshing = '0';
            }
        }

        document.addEventListener('shown.bs.modal', function (event) {
            const modal = event.target && event.target.matches(modalSelector) ? event.target : null;
            if (modal) refreshModalDetail(modal);
        });

        document.addEventListener('click', function (event) {
            const btn = event.target && event.target.closest('.js-refresh-detail');
            if (!btn) return;
            const modalId = (btn.getAttribute('data-modal-id') || '').trim();
            if (!modalId) return;
            const modal = document.getElementById(modalId);
            if (!modal) return;
            refreshModalDetail(modal, true);
        });

        if (window.jQuery) {
            window.jQuery(document).on('shown.bs.modal', modalSelector, function () {
                refreshModalDetail(this);
            });
            window.jQuery(document).on('click', '.js-refresh-detail', function () {
                const modalId = String(window.jQuery(this).data('modal-id') || '').trim();
                if (!modalId) return;
                const modal = document.getElementById(modalId);
                if (!modal) return;
                refreshModalDetail(modal, true);
            });
        }
    })();
</script>

@endsection
