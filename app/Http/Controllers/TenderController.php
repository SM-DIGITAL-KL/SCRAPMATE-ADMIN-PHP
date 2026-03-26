<?php

namespace App\Http\Controllers;

use App\Services\NodeApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\Process\Process;

class TenderController extends Controller
{
    protected $nodeApi;

    public function __construct(NodeApiService $nodeApi)
    {
        $this->nodeApi = $nodeApi;
    }

    public function index()
    {
        $request = request();
        $isTenderV2 = $request->routeIs('tenders.v2');
        $pageName = $isTenderV2 ? 'Tender V2' : 'Tenders';
        $states = $this->indianStates();
        $selectedState = trim((string) $request->query('state', ''));
        $bidassistAuthToken = trim((string) $request->query('bidassist_auth_token', ''));
        $v2ScrapePage = max(0, (int) $request->query('scrape_page', 0));
        $v2SyncRequested = $isTenderV2 && trim((string) $request->query('sync', '')) === '1';
        $appliedRequestId = trim((string) $request->query('request_id', ''));
        $appliedRequestUserId = (int) $request->query('request_user_id', 0);
        $appliedRequestState = trim((string) $request->query('request_state', ''));
        $sortBy = trim((string) $request->query('sort', 'state_asc'));
        $page = max((int) $request->query('page', 1), 1);
        $cached = collect(Cache::get('tenders_data', []));
        $lastRefresh = Cache::get('tenders_last_refresh');
        $tenders = $cached;
        $tenderRequests = collect([]);
        $parseMode = $cached->isEmpty() ? '-' : 'cache';
        $saveError = null;
        $savedTenders = 0;
        $savedDocs = 0;
        $skippedTenders = 0;

        try {
            $savedTendersQuery = [];
            if ($selectedState !== '' && in_array($selectedState, $states, true)) {
                $savedTendersQuery['state'] = $selectedState;
            }
            $nodeResponse = $this->nodeApi->get('/accounts/tenders-saved', $savedTendersQuery, 120);
            if (($nodeResponse['status'] ?? 'error') === 'success' && !empty($nodeResponse['data']['tenders']) && is_array($nodeResponse['data']['tenders'])) {
                $tenders = $this->mapSavedTendersToCollection($nodeResponse['data']['tenders']);
                $parseMode = 'node_saved_api';
                Cache::put('tenders_data', $tenders->values()->toArray(), 60 * 60);
                Cache::put('tenders_last_refresh', now()->format('Y-m-d H:i:s'), 24 * 60 * 60);
                $lastRefresh = now()->format('Y-m-d H:i:s');
            }
        } catch (\Throwable $e) {
            $saveError = 'Failed to load from Node API: ' . $e->getMessage();
            Log::warning('Tender index API fetch failed', ['error' => $e->getMessage()]);
        }

        if ($isTenderV2) {
            try {
                $tenderRequestsQuery = [];
                $tenderRequestsResponse = $this->nodeApi->get('/accounts/tender-requests', $tenderRequestsQuery, 60);
                if (($tenderRequestsResponse['status'] ?? 'error') === 'success' && is_array($tenderRequestsResponse['data']['requests'] ?? null)) {
                    $tenderRequests = collect($tenderRequestsResponse['data']['requests'])
                        ->sortByDesc(function ($row) {
                            return strtotime((string) ($row['created_at'] ?? '')) ?: 0;
                        })
                        ->values();
                }
            } catch (\Throwable $e) {
                Log::warning('Tender V2 request list fetch failed', ['error' => $e->getMessage()]);
            }
        }

        $perPage = $isTenderV2 ? 10 : (int) $request->query('per_page', 5);
        if (!in_array($perPage, [5, 10, 20, 50], true)) {
            $perPage = 5;
        }

        // If a state is selected, fetch first 10 tenders live via bidassist-scraper.
        if ($selectedState !== '' && in_array($selectedState, $states, true)) {
            try {
                if ($isTenderV2) {
                    if ($v2SyncRequested) {
                        if ($bidassistAuthToken !== '') {
                            $v2Result = $this->fetchStateTendersFromScrapeTenderScript($selectedState, $v2ScrapePage, $bidassistAuthToken);
                            $stateSourceUrl = $this->buildBidassistStateUrl($selectedState, $v2ScrapePage, 10);
                            $persist = $this->persistTenderV2ScrapedTenders(collect($v2Result['items'] ?? []), $stateSourceUrl);
                            $savedTenders = (int) ($persist['saved_tenders'] ?? 0);
                            $savedDocs = (int) ($persist['saved_docs'] ?? 0);
                            $skippedTenders = (int) ($persist['skipped_tenders'] ?? 0);
                            if (!empty($persist['error'])) {
                                $saveError = (string) $persist['error'];
                            }
                            $parseMode = 'scrape_tender_codejs_v2_synced';
                            $lastRefresh = now()->format('Y-m-d H:i:s');
                        } else {
                            $saveError = 'BIDASSIST auth token is required to update Tender V2 from scraper.';
                        }
                    }

                    // Tender V2 always renders from saved database after optional sync.
                    try {
                        $savedTendersQuery = [];
                        if ($selectedState !== '' && in_array($selectedState, $states, true)) {
                            $savedTendersQuery['state'] = $selectedState;
                        }
                        $nodeResponse = $this->nodeApi->get('/accounts/tenders-saved', $savedTendersQuery, 120);
                        if (($nodeResponse['status'] ?? 'error') === 'success' && !empty($nodeResponse['data']['tenders']) && is_array($nodeResponse['data']['tenders'])) {
                            $tenders = $this->mapSavedTendersToCollection($nodeResponse['data']['tenders']);
                            $parseMode = $parseMode === 'cache' ? 'node_saved_api' : $parseMode;
                        }
                    } catch (\Throwable $inner) {
                        Log::warning('Tender V2 reload from DB failed', ['error' => $inner->getMessage()]);
                    }

                    // If this sync came from a requested tender row, remove that request and notify the requester.
                    if (
                        $v2SyncRequested &&
                        $saveError === null &&
                        $appliedRequestId !== '' &&
                        $appliedRequestUserId > 0
                    ) {
                        try {
                            $this->nodeApi->post('/accounts/tender-requests/fulfill', [
                                'request_id' => $appliedRequestId,
                                'user_id' => $appliedRequestUserId,
                                'requested_state' => $appliedRequestState !== '' ? $appliedRequestState : $selectedState,
                            ], 60);
                        } catch (\Throwable $fulfillError) {
                            Log::warning('Tender V2 request fulfill call failed', [
                                'request_id' => $appliedRequestId,
                                'user_id' => $appliedRequestUserId,
                                'error' => $fulfillError->getMessage(),
                            ]);
                        }
                    }
                } else {
                    // Pull enough items for current pagination view (up to 6 pages from source).
                    $requestedCount = max(10, min(60, $perPage * $page));
                    $fetchedStateTenders = $this->fetchStateTendersFromBidassistScraper($selectedState, $requestedCount, 6, $bidassistAuthToken);
                    if ($fetchedStateTenders->isNotEmpty()) {
                        // Guard against duplicate items returned by source before persisting.
                        $fetchedStateTenders = $fetchedStateTenders
                            ->unique(function ($tender) {
                                return $this->buildTenderHash((array) $tender);
                            })
                            ->values()
                            ->take($requestedCount);

                        // Persist newly fetched tenders, while skipping already saved ones.
                        $stateSourceUrl = $this->buildBidassistStateUrl($selectedState, 0, 10);
                        $persist = $this->persistTenderDetails($fetchedStateTenders, $stateSourceUrl);
                        $savedTenders = (int) ($persist['saved_tenders'] ?? 0);
                        $savedDocs = (int) ($persist['saved_docs'] ?? 0);
                        $skippedTenders = (int) ($persist['skipped_tenders'] ?? 0);
                        if (!empty($persist['error'])) {
                            $saveError = (string) $persist['error'];
                        }

                        $tenders = $fetchedStateTenders;
                        $parseMode = 'bidassist_scraper_state_persisted';
                        $lastRefresh = now()->format('Y-m-d H:i:s');
                    } else {
                        $saveError = 'No tenders returned for selected state via bidassist-scraper.';
                    }
                }
            } catch (\Throwable $e) {
                $saveError = 'State scrape failed: ' . $e->getMessage();
                Log::warning('State tender scrape failed', [
                    'state' => $selectedState,
                    'error' => $e->getMessage(),
                    'route' => $isTenderV2 ? 'tenders.v2' : 'tenders.index',
                ]);
            }
        }

        if ($selectedState !== '' && in_array($selectedState, $states, true)) {
            $tenders = $tenders->filter(function ($t) use ($selectedState) {
                return trim((string) ($t['state'] ?? '')) === $selectedState;
            })->values();
        }

        if (!$isTenderV2) {
            switch ($sortBy) {
                case 'state_desc':
                    $tenders = $tenders->sortByDesc(function ($t) {
                        return strtolower(trim((string) ($t['state'] ?? '')));
                    })->values();
                    break;
                case 'title_asc':
                    $tenders = $tenders->sortBy(function ($t) {
                        return strtolower(trim((string) ($t['title'] ?? '')));
                    })->values();
                    break;
                case 'title_desc':
                    $tenders = $tenders->sortByDesc(function ($t) {
                        return strtolower(trim((string) ($t['title'] ?? '')));
                    })->values();
                    break;
                case 'state_asc':
                default:
                    $tenders = $tenders->sortBy(function ($t) {
                        return strtolower(trim((string) ($t['state'] ?? '')));
                    })->values();
                    break;
            }
        }

        $total = $tenders->count();
        $items = $tenders->forPage($page, $perPage)->values();
        $paginatedTenders = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => (function () use ($request, $isTenderV2) {
                    $query = $request->query();
                    if ($isTenderV2) {
                        // Keep bottom pagination DB-only; do not carry scraper trigger params.
                        unset($query['sync'], $query['scrape_page'], $query['bidassist_auth_token']);
                    }
                    return $query;
                })(),
            ]
        );

        return view('admin.tenders', [
            'pagename' => $pageName,
            'tenders' => $paginatedTenders,
            'states' => $states,
            'selectedState' => $selectedState,
            'bidassistAuthToken' => $bidassistAuthToken,
            'sortBy' => $sortBy,
            'perPage' => $perPage,
            'tenderRequests' => $tenderRequests,
            'sourceUrl' => $this->defaultBidAssistUrl(),
            'rawContent' => '',
            'meta' => [
                'fetched_at' => $lastRefresh ?: '-',
                'source_url' => $this->defaultBidAssistUrl(),
                'total' => $total,
                'parse_mode' => $parseMode,
                'saved_tenders' => $savedTenders,
                'saved_docs' => $savedDocs,
                'skipped_tenders' => $skippedTenders,
                'save_error' => $saveError,
            ]
        ]);
    }

    public function downloadV2Document(Request $request)
    {
        $encoded = trim((string) $request->query('p', ''));
        if ($encoded === '') {
            abort(404);
        }

        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);
        if (!is_string($decoded) || trim($decoded) === '') {
            abort(404);
        }

        $realPath = realpath($decoded);
        if ($realPath === false || !is_file($realPath)) {
            abort(404);
        }

        $allowedRoots = $this->resolveScrapeTenderOutputRoots();
        $isAllowed = false;
        foreach ($allowedRoots as $allowedRoot) {
            if ($allowedRoot !== '' && str_starts_with($realPath, $allowedRoot . DIRECTORY_SEPARATOR)) {
                $isAllowed = true;
                break;
            }
        }
        if (!$isAllowed) {
            abort(403);
        }

        return response()->download($realPath, basename($realPath));
    }

    public function fixSavedV2Data(Request $request)
    {
        $state = trim((string) $request->input('state', ''));
        $bidassistAuthToken = trim((string) $request->input('bidassist_auth_token', ''));
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        try {
            $nodeResponse = $this->nodeApi->get('/accounts/tenders-saved', [], 180);
            if (($nodeResponse['status'] ?? 'error') !== 'success' || empty($nodeResponse['data']['tenders']) || !is_array($nodeResponse['data']['tenders'])) {
                return redirect()->route('tenders.v2', ['state' => $state])->with('error', 'Failed to load saved tenders from database.');
            }

            $savedRows = collect($nodeResponse['data']['tenders']);
            if ($state !== '' && in_array($state, $this->indianStates(), true)) {
                $savedRows = $savedRows->filter(function ($row) use ($state) {
                    $location = (string) ($row['location'] ?? '');
                    return $this->extractIndianState($location) === $state;
                })->values();
            }

            $savedRows = $savedRows->take(500);
            foreach ($savedRows as $row) {
                try {
                    $mapped = $this->mapSavedTendersToCollection([(array) $row])->first();
                    if (!is_array($mapped)) {
                        $skipped++;
                        continue;
                    }

                    $currentTenderUrl = trim((string) (($row['tender_url'] ?? '') ?: ($mapped['url'] ?? '')));
                    $currentWebsite = trim((string) (($row['website'] ?? '') ?: ($mapped['raw']['website'] ?? '')));
                    $hasBadTenderUrl = $currentTenderUrl === '' || (!str_starts_with(strtolower($currentTenderUrl), 'http://') && !str_starts_with(strtolower($currentTenderUrl), 'https://'));
                    $hasBadWebsite = $currentWebsite === '' || (!str_starts_with(strtolower($currentWebsite), 'http://') && !str_starts_with(strtolower($currentWebsite), 'https://'));

                    $docs = is_array($row['documents'] ?? null) ? $row['documents'] : [];
                    $missingDownloadableDocs = collect($docs)->contains(function ($doc) {
                        $s3 = trim((string) ($doc['s3_url'] ?? ''));
                        $url = trim((string) (($doc['doc_url'] ?? '') ?: ($doc['docUrl'] ?? '')));
                        $hasS3 = str_starts_with(strtolower($s3), 'http://') || str_starts_with(strtolower($s3), 'https://');
                        $hasUrl = str_starts_with(strtolower($url), 'http://') || str_starts_with(strtolower($url), 'https://');
                        return !$hasS3 && !$hasUrl;
                    });

                    if (!$hasBadTenderUrl && !$hasBadWebsite && !$missingDownloadableDocs) {
                        $skipped++;
                        continue;
                    }

                    $detailUrl = trim((string) (($row['source_url'] ?? '') ?: ($row['tender_url'] ?? '') ?: ($mapped['url'] ?? '')));
                    $trace = [];
                    if ($detailUrl !== '' && str_contains($detailUrl, '/detail-')) {
                        $detail = $this->fetchDetailFromBidassistScraper($detailUrl, $trace, $bidassistAuthToken);
                        if (is_array($detail)) {
                            $mapped = $this->mergeBidassistDetailIntoTender($mapped, $detail);
                        }
                    }

                    if (trim((string) ($mapped['raw']['website'] ?? '')) === '' && $currentWebsite !== '') {
                        $mapped['raw']['website'] = $currentWebsite;
                    }
                    if (trim((string) ($mapped['raw']['tender_url'] ?? '')) === '') {
                        $fallbackUrl = trim((string) (($mapped['url'] ?? '') ?: ($mapped['raw']['website'] ?? '')));
                        if ($fallbackUrl !== '') {
                            $mapped['raw']['tender_url'] = $fallbackUrl;
                            $mapped['url'] = $fallbackUrl;
                        }
                    }

                    $mapped = $this->uploadTenderDocumentsToS3($mapped, $trace);
                    $this->syncSingleTenderToAws($mapped, $detailUrl !== '' ? $detailUrl : (string) ($mapped['url'] ?? ''), $trace);
                    $updated++;
                } catch (\Throwable $inner) {
                    $failed++;
                    Log::warning('Tender V2 fix-saved item failed', ['error' => $inner->getMessage()]);
                }
            }

            $msg = 'Fix complete. Updated: ' . $updated . ', Skipped: ' . $skipped . ', Failed: ' . $failed . '.';
            return redirect()->route('tenders.v2', ['state' => $state])->with($failed > 0 ? 'error' : 'success', $msg);
        } catch (\Throwable $e) {
            Log::error('Tender V2 fix-saved failed', ['error' => $e->getMessage()]);
            return redirect()->route('tenders.v2', ['state' => $state])->with('error', 'Fix saved docs failed: ' . $e->getMessage());
        }
    }

    public function refreshV2TenderDocs(Request $request)
    {
        $trace = [];
        $tenderIdInput = trim((string) $request->input('tender_id', ''));
        $bidassistAuthToken = trim((string) $request->input('bidassist_auth_token', ''));
        if ($tenderIdInput === '') {
            return response()->json(['status' => 'error', 'msg' => 'Tender id is required'], 422);
        }
        if ($bidassistAuthToken === '') {
            return response()->json(['status' => 'error', 'msg' => 'BIDASSIST auth token is required'], 422);
        }

        $tmpJson = null;
        $outputDir = null;
        try {
            $nodeResponse = $this->nodeApi->get('/accounts/tenders-saved', [], 180);
            if (($nodeResponse['status'] ?? 'error') !== 'success' || empty($nodeResponse['data']['tenders']) || !is_array($nodeResponse['data']['tenders'])) {
                return response()->json(['status' => 'error', 'msg' => 'Failed to load saved tenders'], 500);
            }

            $rows = collect($nodeResponse['data']['tenders']);
            $row = $rows->first(function ($r) use ($tenderIdInput) {
                $payloadRaw = $this->extractRawPayloadFromSavedRow((array) $r);
                $ids = [
                    strtolower(trim((string) ($r['tender_id'] ?? ''))),
                    strtolower(trim((string) ($r['tender_no'] ?? ''))),
                    strtolower(trim((string) ($r['source_tender_id'] ?? ''))),
                    strtolower(trim((string) ($r['tender_notice_no'] ?? ''))),
                    strtolower(trim((string) ($payloadRaw['source_tender_id'] ?? ''))),
                    strtolower(trim((string) ($payloadRaw['sourceTenderId'] ?? ''))),
                    strtolower(trim((string) ($payloadRaw['tender_notice_no'] ?? ''))),
                    strtolower(trim((string) ($payloadRaw['tenderNoticeNo'] ?? ''))),
                    strtolower(trim((string) ($payloadRaw['tenderId'] ?? ''))),
                    strtolower(trim((string) ($payloadRaw['tenderNo'] ?? ''))),
                ];
                return in_array(strtolower($tenderIdInput), $ids, true);
            });
            if (!is_array($row)) {
                return response()->json(['status' => 'error', 'msg' => 'Tender not found in saved DB'], 404);
            }

            $rawPayload = json_decode((string) ($row['raw_payload'] ?? ''), true);
            $raw = is_array($rawPayload['raw'] ?? null) ? $rawPayload['raw'] : [];
            $sourceTender = is_array($raw['source_tender'] ?? null) ? $raw['source_tender'] : $raw;

            $tenderId = $this->firstMeaningfulValue([
                $row['source_tender_id'] ?? '',
                $row['tender_id'] ?? '',
                $sourceTender['source_tender_id'] ?? '',
                $sourceTender['sourceTenderId'] ?? '',
                $sourceTender['tender_notice_no'] ?? '',
                $sourceTender['tenderNoticeNo'] ?? '',
                $row['tender_notice_no'] ?? '',
                $row['tender_no'] ?? '',
                $sourceTender['tenderId'] ?? '',
            ]);

            $detailDocs = is_array($sourceTender['detail']['documents'] ?? null) ? $sourceTender['detail']['documents'] : [];
            if (empty($detailDocs) && is_array($sourceTender['documents'] ?? null)) {
                $detailDocs = $sourceTender['documents'];
            }
            if (empty($detailDocs) && is_array($sourceTender['downloadedDocuments'] ?? null)) {
                $detailDocs = collect($sourceTender['downloadedDocuments'])->map(function ($doc) {
                    return [
                        'name' => (string) ($doc['name'] ?? ''),
                        'title' => (string) ($doc['title'] ?? ''),
                        'documentId' => $doc['documentId'] ?? null,
                        'cipherDocumentId' => (string) (($doc['cipherDocumentId'] ?? '') ?: ($doc['usedId'] ?? '')),
                    ];
                })->filter(function ($doc) {
                    return trim((string) ($doc['documentId'] ?? '')) !== '' || trim((string) ($doc['cipherDocumentId'] ?? '')) !== '';
                })->values()->toArray();
            }

            $detailUrl = $this->firstMeaningfulValue([
                $row['source_url'] ?? '',
                $sourceTender['detailUrl'] ?? '',
                $sourceTender['url'] ?? '',
                $row['tender_url'] ?? '',
            ]);
            if (($tenderId === '' || empty($detailDocs)) && $detailUrl !== '' && str_contains($detailUrl, '/detail-')) {
                $fetched = $this->fetchTenderV2DetailDocuments($detailUrl, $bidassistAuthToken);
                if (!empty($fetched['tender_id'])) {
                    $tenderId = (string) $fetched['tender_id'];
                }
                if (!empty($fetched['documents']) && is_array($fetched['documents'])) {
                    $detailDocs = $fetched['documents'];
                }
            }
            if ($tenderId === '' || empty($detailDocs)) {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'No detail documents found for this tender. Use a valid token and try again.',
                ], 422);
            }

            $tmpJsonBase = tempnam(sys_get_temp_dir(), 'v2docs_');
            $tmpJson = $tmpJsonBase . '.json';
            @rename($tmpJsonBase, $tmpJson);
            $outputDir = sys_get_temp_dir() . '/v2docs_' . uniqid();
            @mkdir($outputDir, 0777, true);

            file_put_contents($tmpJson, json_encode([
                'tenders' => [[
                    'tenderId' => $tenderId,
                    'detail' => ['documents' => $detailDocs],
                ]],
            ], JSON_PRETTY_PRINT));

            $scriptPath = $this->resolveScrapeTenderDir() . '/download-bidassist-docs.js';
            if (!file_exists($scriptPath)) {
                return response()->json(['status' => 'error', 'msg' => 'download-bidassist-docs.js not found'], 500);
            }

            $env = array_merge($_ENV, [
                'BIDASSIST_AUTH_TOKEN' => $bidassistAuthToken,
                'BIDASSIST_TENDER_ENTITY' => 'TENDER_LISTING',
            ]);
            $process = new Process(['node', $scriptPath, $tmpJson, $outputDir], dirname(base_path()), $env);
            $process->setTimeout(240);
            $process->run();
            if (!$process->isSuccessful()) {
                return response()->json([
                    'status' => 'error',
                    'msg' => trim($process->getErrorOutput() ?: $process->getOutput() ?: 'Doc refresh failed'),
                ], 500);
            }

            $reportPath = $outputDir . '/download-report.json';
            $report = file_exists($reportPath) ? json_decode((string) file_get_contents($reportPath), true) : null;
            $downloadedItems = collect($report['items'] ?? [])->filter(function ($item) use ($tenderId) {
                return strtolower(trim((string) ($item['tenderId'] ?? ''))) === strtolower($tenderId)
                    && strtolower(trim((string) ($item['status'] ?? ''))) === 'downloaded'
                    && trim((string) ($item['savedTo'] ?? '')) !== '';
            })->values();

            if ($downloadedItems->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'No downloadable documents found for this tender with current auth token.',
                ], 422);
            }

            $mapped = $this->mapSavedTendersToCollection([$row])->first();
            if (!is_array($mapped)) {
                return response()->json(['status' => 'error', 'msg' => 'Failed to map saved tender'], 500);
            }

            $mapped['documents'] = $downloadedItems->map(function ($item) {
                $name = trim((string) (($item['name'] ?? '') ?: ($item['title'] ?? 'Document')));
                return [
                    'doc_label' => (string) (($item['title'] ?? '') ?: $name),
                    'file_name' => $name !== '' ? $name : 'Document',
                    'file_size' => '',
                    'doc_url' => (string) $item['savedTo'],
                    's3_key' => '',
                    's3_url' => '',
                ];
            })->toArray();

            $mapped = $this->uploadTenderDocumentsToS3($mapped, $trace);
            $detailUrl = trim((string) (($row['source_url'] ?? '') ?: ($mapped['url'] ?? '')));
            $this->syncSingleTenderToAws($mapped, $detailUrl, $trace);

            return response()->json([
                'status' => 'success',
                'msg' => 'Tender documents refreshed from scrape tender flow.',
                'data' => $this->formatRefreshDetailResponseData($mapped),
                'meta' => [
                    'source' => 'scrape_tender_download_docs',
                    'trace' => $trace,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Tender V2 doc refresh failed', ['error' => $e->getMessage(), 'tender_id' => $tenderIdInput]);
            return response()->json(['status' => 'error', 'msg' => 'Failed: ' . $e->getMessage()], 500);
        } finally {
            if (is_string($tmpJson) && file_exists($tmpJson)) {
                @unlink($tmpJson);
            }
        }
    }

    private function fetchTenderV2DetailDocuments(string $detailUrl, string $bidassistAuthToken = ''): array
    {
        try {
            $headers = [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Referer' => 'https://bidassist.com/',
            ];
            if ($bidassistAuthToken !== '') {
                $headers['Authorization'] = 'Bearer ' . $bidassistAuthToken;
                $headers['X-OFB-TOKEN'] = $bidassistAuthToken;
            }

            $res = Http::timeout(90)->withHeaders($headers)->get($detailUrl);
            if (!$res->successful()) {
                return ['tender_id' => '', 'documents' => []];
            }

            $html = (string) $res->body();
            $marker = 'window.__INITIAL_STATE__=';
            $start = strpos($html, $marker);
            if ($start === false) {
                return ['tender_id' => '', 'documents' => []];
            }
            $start += strlen($marker);
            $end = strpos($html, '</script>', $start);
            if ($end === false) {
                return ['tender_id' => '', 'documents' => []];
            }

            $jsonText = trim(substr($html, $start, $end - $start));
            $state = json_decode($jsonText, true);
            if (!is_array($state)) {
                return ['tender_id' => '', 'documents' => []];
            }

            $tender = is_array($state['tender'] ?? null) ? $state['tender'] : [];
            return [
                'tender_id' => (string) ($tender['tenderId'] ?? ''),
                'documents' => is_array($tender['documents'] ?? null) ? $tender['documents'] : [],
            ];
        } catch (\Throwable $e) {
            return ['tender_id' => '', 'documents' => []];
        }
    }

    public function fetch(Request $request)
    {
        $sourceUrl = trim((string) $request->input('source_url', ''));
        $rawContent = trim((string) $request->input('raw_content', ''));
        // Force canonical Kerala URL; avoid old /tender-page-* variants.
        $sourceUrl = $this->canonicalBidAssistUrl($sourceUrl);
        $useNodeFetchFallback = true; // Last-resort fallback when admin parsing returns empty

        try {
            // Browsershot-only fetch mode (requested): do not use admin parser / node fallback / puppeteer script for listing.
            $browsershot = $this->runBrowsershotScrape($sourceUrl);
            $normalized = collect([]);
            $parseMode = 'browsershot_only';

            if (!empty($browsershot['ok']) && !empty($browsershot['items']) && is_array($browsershot['items'])) {
                $normalized = collect($browsershot['items'])->take(10)->values();
            } else {
                $cached = collect(Cache::get('tenders_data', []));
                if (!$cached->isEmpty()) {
                    $normalized = $cached->values();
                    $parseMode = 'cache_fallback';
                }
            }

            $persist = [
                'saved_tenders' => 0,
                'saved_docs' => 0,
                'skipped_tenders' => 0,
                'error' => null,
                'tenders' => [],
            ];

            if (!$normalized->isEmpty()) {
                Cache::put('tenders_data', $normalized->values()->toArray(), 60 * 60);
                Cache::put('tenders_last_refresh', now()->format('Y-m-d H:i:s'), 24 * 60 * 60);
                session()->flash('success', 'Fetched ' . $normalized->count() . ' tenders successfully.');
                $persist = $this->persistTenderDetails($normalized, $sourceUrl);
            } else {
                $err = (string) ($browsershot['error'] ?? 'Browsershot returned no tenders.');
                session()->flash('error', 'Fetch failed in Browsershot-only mode: ' . $err);
            }

            if (!empty($persist['tenders'])) {
                $normalized = collect($persist['tenders']);
            }

            $meta = [
                'fetched_at' => now()->format('Y-m-d H:i:s'),
                'source_url' => $sourceUrl,
                'total' => $normalized->count(),
                'parse_mode' => $parseMode,
                'saved_tenders' => $persist['saved_tenders'],
                'saved_docs' => $persist['saved_docs'],
                'skipped_tenders' => $persist['skipped_tenders'] ?? 0,
                'save_error' => $persist['error'],
            ];

            return view('admin.tenders', [
                'pagename' => 'Tenders',
                'tenders' => $normalized,
                'sourceUrl' => $sourceUrl,
                'rawContent' => $rawContent,
                'meta' => $meta,
            ]);

            // Manual raw content takes precedence (useful when source blocks bots with 403)
            if ($rawContent !== '') {
                $normalized = $this->mergeTenderCandidates(
                    $this->parseJinaMarkdownTenders($rawContent),
                    $this->parseRawTenderText($rawContent)
                );
                $normalized = $this->filterKeralaOnly($normalized)->take(10)->values();
                $persist = [
                    'saved_tenders' => 0,
                    'saved_docs' => 0,
                    'skipped_tenders' => 0,
                    'error' => null,
                    'tenders' => [],
                ];
                if (!$normalized->isEmpty()) {
                    $persist = $this->persistTenderDetails($normalized, $sourceUrl);
                }
                if (!empty($persist['tenders'])) {
                    $normalized = collect($persist['tenders']);
                }
                if (!$normalized->isEmpty()) {
                    Cache::put('tenders_data', $normalized->values()->toArray(), 60 * 60);
                    Cache::put('tenders_last_refresh', now()->format('Y-m-d H:i:s'), 24 * 60 * 60);
                    session()->flash('success', 'Fetched ' . $normalized->count() . ' tenders successfully.');
                } else {
                    session()->flash('error', 'No tenders parsed from raw content.');
                }

                return view('admin.tenders', [
                    'pagename' => 'Tenders',
                    'tenders' => $normalized,
                    'sourceUrl' => $sourceUrl,
                    'rawContent' => $rawContent,
                    'meta' => [
                        'fetched_at' => now()->format('Y-m-d H:i:s'),
                        'source_url' => $sourceUrl,
                        'total' => $normalized->count(),
                        'parse_mode' => 'raw_text',
                        'saved_tenders' => $persist['saved_tenders'],
                        'saved_docs' => $persist['saved_docs'],
                        'skipped_tenders' => $persist['skipped_tenders'] ?? 0,
                        'save_error' => $persist['error'],
                    ],
                ]);
            }

            $response = Http::timeout(40)
                ->withHeaders([
                    'Accept' => 'application/json,text/html;q=0.9,*/*;q=0.8',
                    'User-Agent' => 'Mozilla/5.0 (compatible; ScrapmateAdmin/1.0)',
                ])
                ->get($sourceUrl);

            $parseMode = 'unknown';
            $normalized = collect([]);

            if (!$response->ok()) {
                if ($response->status() !== 403) {
                    return redirect()
                        ->route('tenders.index')
                        ->with('error', 'Failed to fetch tenders. HTTP status: ' . $response->status());
                }
                // 403: fallback to jina AI mirror
                $proxyBody = $this->fetchViaJina($sourceUrl);
                $normalized = $this->mergeTenderCandidates(
                    $this->parseJinaMarkdownTenders($proxyBody),
                    $this->parseRawTenderText($proxyBody)
                );
                $parseMode = 'jina_proxy';
            } else {
                $body = (string) $response->body();
                $parsed = $this->parseTenderPayload($body);
                $normalized = collect($parsed['items'] ?? [])->map(function ($item, $idx) {
                    return $this->normalizeTender($item, $idx + 1);
                })->filter(function ($item) {
                    return !empty($item['title']) || !empty($item['reference_no']) || !empty($item['authority']);
                })->values();
                $parseMode = $parsed['mode'] ?? 'unknown';

                if ($normalized->isEmpty()) {
                    // HTML/text fallback on direct response
                    $normalized = $this->mergeTenderCandidates(
                        $this->parseJinaMarkdownTenders(strip_tags($body)),
                        $this->parseRawTenderText(strip_tags($body))
                    );
                    $parseMode = $normalized->isEmpty() ? $parseMode : 'html_text_fallback';
                }
            }

            if ($normalized->isEmpty()) {
                // Final attempt via proxy if direct parse gave nothing
                $proxyBody = $this->fetchViaJina($sourceUrl);
                $normalized = $this->mergeTenderCandidates(
                    $this->parseJinaMarkdownTenders($proxyBody),
                    $this->parseRawTenderText($proxyBody)
                );
                if (!$normalized->isEmpty()) {
                    $parseMode = 'jina_proxy';
                }
            }

            if ($normalized->count() < 10) {
                // Alternate source fallback for Kerala scraps feed shape
                $alternateUrls = [
                    'https://bidassist.com/all-tenders/active?filter=KEYWORD:scrap&filter=CATEGORY:Scraps&filter=LOCATION_STRING:Kerala&sort=RELEVANCE:DESC&pageNumber=0&pageSize=10&tenderType=ACTIVE&tenderEntity=TENDER_LISTING&year=2026&removeUnavailableTenderAmountCards=false&removeUnavailableEmdCards=false',
                    'https://bidassist.com/all-tenders/active/tender-page-5?filter=KEYWORD:scrap&filter=CATEGORY:Scraps&sort=RELEVANCE:DESC&pageNumber=0&pageSize=10&tenderType=ACTIVE&tenderEntity=TENDER_LISTING&year=2026&removeUnavailableTenderAmountCards=false&removeUnavailableEmdCards=false',
                ];
                foreach ($alternateUrls as $altUrl) {
                    try {
                        $altBody = $this->fetchViaJina($altUrl);
                        $normalized = $this->mergeTenderCandidates(
                            $normalized,
                            $this->mergeTenderCandidates(
                                $this->parseJinaMarkdownTenders($altBody),
                                $this->parseRawTenderText($altBody)
                            )
                        );
                        if ($normalized->count() >= 10) {
                            break;
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Tender alt source parse failed', ['url' => $altUrl, 'err' => $e->getMessage()]);
                    }
                }
                if ($normalized->isNotEmpty()) {
                    $parseMode = $parseMode === 'unknown' ? 'jina_alt_sources' : ($parseMode . '+jina_alt_sources');
                }
            }

            if ($normalized->isEmpty() && $useNodeFetchFallback) {
                // Fallback: use Node API parser endpoint to avoid empty admin UI
                $nodeResponse = $this->nodeApi->get('/accounts/tenders-fetch-kerala-scraps', [], 120);
                if (($nodeResponse['status'] ?? 'error') === 'success' && !empty($nodeResponse['data']['tenders']) && is_array($nodeResponse['data']['tenders'])) {
                    $normalized = $this->mapNodeTendersToCollection($nodeResponse['data']['tenders']);
                    $parseMode = 'node_api_fallback';
                }
            }

            if ($normalized->isEmpty()) {
                // PHP package fallback (Composer): spatie/browsershot
                $browsershot = $this->runBrowsershotScrape($sourceUrl);
                if (!empty($browsershot['ok']) && !empty($browsershot['items']) && is_array($browsershot['items'])) {
                    $normalized = collect($browsershot['items'])->values();
                    $parseMode = 'browsershot_fallback';
                } elseif (!empty($browsershot['error'])) {
                    Log::warning('Browsershot fallback failed', ['error' => $browsershot['error']]);
                }
            }

            if ($normalized->isEmpty()) {
                // Last fallback: run local Puppeteer scraper from admin panel environment
                $puppeteer = $this->runPuppeteerScrape($sourceUrl);
                if (!empty($puppeteer['ok']) && !empty($puppeteer['items']) && is_array($puppeteer['items'])) {
                    $normalized = $this->mapNodeTendersToCollection($puppeteer['items']);
                    $parseMode = 'puppeteer_fallback';
                } elseif (!empty($puppeteer['error'])) {
                    Log::warning('Puppeteer fallback failed', ['error' => $puppeteer['error']]);
                }
            }
            $rawNormalized = $normalized;
            $normalized = $this->filterKeralaOnly($normalized)->take(10)->values();
            if ($normalized->isEmpty()) {
                $cached = collect(Cache::get('tenders_data', []));
                if (!$cached->isEmpty()) {
                    $normalized = $cached->values();
                    $parseMode = 'cache_fallback';
                }
                session()->flash('error', 'Fetch completed but no tenders were parsed from admin parser or Node fallback. Try pasting raw content.');
            } else {
                Cache::put('tenders_data', $normalized->values()->toArray(), 60 * 60);
                Cache::put('tenders_last_refresh', now()->format('Y-m-d H:i:s'), 24 * 60 * 60);
                session()->flash('success', 'Fetched ' . $normalized->count() . ' tenders successfully.');
            }

            $persist = [
                'saved_tenders' => 0,
                'saved_docs' => 0,
                'skipped_tenders' => 0,
                'error' => null,
                'tenders' => [],
            ];
            if (!$normalized->isEmpty()) {
                $persist = $this->persistTenderDetails($normalized, $sourceUrl);
            }
            if (!empty($persist['tenders'])) {
                $normalized = collect($persist['tenders']);
            }

            $meta = [
                'fetched_at' => now()->format('Y-m-d H:i:s'),
                'source_url' => $sourceUrl,
                'total' => $normalized->count(),
                'parse_mode' => $parseMode,
                'saved_tenders' => $persist['saved_tenders'],
                'saved_docs' => $persist['saved_docs'],
                'skipped_tenders' => $persist['skipped_tenders'] ?? 0,
                'save_error' => $persist['error'],
            ];

            return view('admin.tenders', [
                'pagename' => 'Tenders',
                'tenders' => $normalized,
                'sourceUrl' => $sourceUrl,
                'rawContent' => $rawContent,
                'meta' => $meta,
            ]);
        } catch (\Throwable $e) {
            Log::error('Tender fetch failed', [
                'url' => $sourceUrl,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('tenders.index')
                ->with('error', 'Failed to fetch tenders: ' . $e->getMessage());
        }
    }

    public function refreshDetail(Request $request)
    {
        $trace = [];
        $detailUrl = trim((string) $request->input('detail_url', ''));
        $bidassistAuthToken = trim((string) $request->input('bidassist_auth_token', ''));
        $trace[] = 'Request received';
        if ($detailUrl === '' || !str_contains($detailUrl, '/detail-')) {
            return response()->json([
                'status' => 'error',
                'msg' => 'Invalid detail URL',
                'meta' => ['trace' => $trace],
            ], 422);
        }
        $trace[] = 'Validated detail URL';

        $baseTender = [
            'title' => trim((string) $request->input('title', '')),
            'authority' => trim((string) $request->input('authority', '')),
            'location' => trim((string) $request->input('location', '')),
            'url' => $detailUrl,
            'documents' => [],
            'raw' => [],
        ];

        try {
            $cacheKey = $this->tenderDetailCacheKey($detailUrl);
            $cachedTender = Cache::get($cacheKey);
            if (is_array($cachedTender) && $this->hasDownloadableDocumentLinks(is_array($cachedTender['documents'] ?? null) ? $cachedTender['documents'] : [])) {
                $trace[] = 'Using cached detail with downloadable documents';
                $trace[] = 'Skipped scraper run';
                return response()->json([
                    'status' => 'success',
                    'data' => $this->formatRefreshDetailResponseData($cachedTender),
                    'meta' => [
                        'source' => 'cached_detail',
                        'trace' => $trace,
                    ],
                ]);
            }

            $detail = null;
            $source = '';
            // Retry detail scrape twice; dynamic pages intermittently fail on first load.
            for ($attempt = 0; $attempt < 2; $attempt++) {
                $trace[] = 'Running node scraper attempt ' . ($attempt + 1);
                $attemptDebug = [];
                $detail = $this->fetchDetailFromBidassistScraper($detailUrl, $attemptDebug, $bidassistAuthToken);
                foreach ($attemptDebug as $line) {
                    $trace[] = $line;
                }
                if ($detail) {
                    $source = 'node_scraper';
                    $trace[] = 'Node scraper returned detail JSON';
                    break;
                }
                usleep(250000);
            }
            if ($detail) {
                $tender = $this->mergeBidassistDetailIntoTender($baseTender, $detail);
            } else {
                $trace[] = 'Node scraper unavailable; trying markdown fallback';
                $detailMarkdown = $this->fetchViaJina($detailUrl);
                if (trim($detailMarkdown) === '') {
                    return response()->json([
                        'status' => 'error',
                        'msg' => 'Detail scrape returned empty content',
                        'meta' => ['trace' => $trace],
                    ], 422);
                }
                $tender = $this->parseTenderDetailMarkdown($detailMarkdown, $baseTender);
                $source = 'jina_markdown_fallback';
                $trace[] = 'Parsed detail from markdown fallback';
            }

            $tender = $this->uploadTenderDocumentsToS3($tender, $trace);
            $this->syncSingleTenderToAws($tender, $detailUrl, $trace);
            Cache::put($cacheKey, $tender, 24 * 60 * 60);
            $trace[] = 'Prepared contact/documents response';

            return response()->json([
                'status' => 'success',
                'data' => $this->formatRefreshDetailResponseData($tender),
                'meta' => [
                    'source' => $source,
                    'trace' => $trace,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Manual tender detail refresh failed', [
                'detail_url' => $detailUrl,
                'error' => $e->getMessage(),
            ]);
            $trace[] = 'Exception: ' . $e->getMessage();
            return response()->json([
                'status' => 'error',
                'msg' => 'Failed to refresh detail: ' . $e->getMessage(),
                'meta' => ['trace' => $trace],
            ], 500);
        }
    }

    private function tenderDetailCacheKey(string $detailUrl): string
    {
        return 'tender_detail_cached_' . md5(strtolower(trim($detailUrl)));
    }

    private function hasDownloadableDocumentLinks(array $docs): bool
    {
        foreach ($docs as $doc) {
            $s3Url = trim((string) ($doc['s3_url'] ?? ''));
            $docUrl = trim((string) ($doc['doc_url'] ?? ($doc['docUrl'] ?? '')));
            if (str_starts_with(strtolower($s3Url), 'http://') || str_starts_with(strtolower($s3Url), 'https://')) {
                return true;
            }
            if (str_starts_with(strtolower($docUrl), 'http://') || str_starts_with(strtolower($docUrl), 'https://')) {
                return true;
            }
        }
        return false;
    }

    private function formatRefreshDetailResponseData(array $tender): array
    {
        $documentCost = $this->firstMeaningfulValue([
            $tender['raw']['document_cost'] ?? '',
            $tender['raw']['documentCost'] ?? '',
        ]);
        $tenderFee = $this->firstMeaningfulValue([
            $tender['raw']['tender_fee'] ?? '',
            $tender['raw']['tenderFee'] ?? '',
        ]);
        $emd = $this->firstMeaningfulValue([
            $tender['emd'] ?? '',
            $tender['raw']['emd'] ?? '',
        ]);
        $tenderAmount = $this->firstMeaningfulValue([
            $tender['tender_value'] ?? '',
            $tender['tender_amount'] ?? '',
        ]);
        $website = $this->normalizeExternalUrl($this->firstMeaningfulValue([
            $tender['raw']['website'] ?? '',
            $tender['website'] ?? '',
        ]));
        $tenderUrl = $this->normalizeExternalUrl($this->firstMeaningfulValue([
            $tender['raw']['tender_url'] ?? '',
            $tender['url'] ?? '',
            $website,
        ]));
        if ($tenderUrl === '' && str_contains(strtolower($website), 'tntenders.gov.in')) {
            $tenderUrl = 'https://tntenders.gov.in/nicgep/app?component=%24DirectLink&page=FrontEndViewTender&service=direct&session=T';
        }
        if ($website === '' && str_starts_with(strtolower($tenderUrl), 'http')) {
            $website = $tenderUrl;
        }
        return [
            'tender_id' => $this->firstMeaningfulValue([
                $tender['tender_id'] ?? '',
                $tender['raw']['source_tender_id'] ?? '',
                $tender['raw']['sourceTenderId'] ?? '',
                $tender['tender_no'] ?? '',
            ]),
            'tender_no' => $this->firstMeaningfulValue([
                $tender['tender_no'] ?? '',
                $tender['raw']['tender_notice_no'] ?? '',
                $tender['raw']['tenderNoticeNo'] ?? '',
                $tender['raw']['source_tender_id'] ?? '',
                $tender['raw']['sourceTenderId'] ?? '',
            ]),
            'tender_authority' => $this->firstMeaningfulValue([
                $tender['raw']['tender_authority'] ?? '',
                $tender['authority'] ?? '',
            ]),
            'purchaser_address' => $this->firstMeaningfulValue([
                $tender['raw']['purchaser_address'] ?? '',
                $tender['purchaser_address'] ?? '',
            ]),
            'website' => $website,
            'emd' => $emd !== '' ? $emd : 'Refer Documents',
            'document_cost' => $documentCost !== '' ? $documentCost : 'Refer Documents',
            'tender_fee' => $tenderFee !== '' ? $tenderFee : 'Refer Documents',
            'opening_date' => (string) ($tender['opening_date'] ?? ''),
            'closing_date' => (string) ($tender['closing_date'] ?? ''),
            'closing_label' => (string) ($tender['closing_label'] ?? ''),
            'tender_amount' => $tenderAmount !== '' ? $tenderAmount : 'Refer Documents',
            'tender_url' => $tenderUrl,
            'documents' => $this->applyDocumentUrlFallbacks(
                is_array($tender['documents'] ?? null) ? $tender['documents'] : [],
                $tenderUrl !== '' ? $tenderUrl : $website
            ),
            'documents_count' => count($tender['documents'] ?? []),
            'major_source_data' => $this->buildMajorSourceData(
                $this->resolveTenderSourcePayload($tender)
            ),
        ];
    }

    private function mapNodeTendersToCollection(array $tenders)
    {
        return collect($tenders)->map(function ($t, $idx) {
            return [
                'sl_no' => $idx + 1,
                'title' => (string) ($t['title'] ?? ''),
                'reference_no' => (string) ($t['reference_no'] ?? ''),
                'authority' => (string) ($t['authority'] ?? ''),
                'location' => (string) ($t['location'] ?? ''),
                'closing_date' => (string) ($t['closing_date'] ?? ''),
                'closing_label' => (string) ($t['closing_label'] ?? ''),
                'emd' => (string) ($t['emd'] ?? ''),
                'tender_value' => (string) ($t['tender_value'] ?? ($t['pricing'] ?? '')),
                'type' => (string) ($t['type'] ?? ''),
                'services' => (string) ($t['services'] ?? ($t['type'] ?? '')),
                'category' => (string) ($t['category'] ?? 'Scraps'),
                'platform' => (string) ($t['platform'] ?? ($t['keyword'] ?? '')),
                'keyword' => (string) ($t['keyword'] ?? ($t['platform'] ?? '')),
                'description' => (string) ($t['description'] ?? ''),
                'url' => (string) ($t['url'] ?? ''),
                'phone_number' => (string) ($t['phone_number'] ?? ''),
                'raw' => $t,
            ];
        })->filter(function ($t) {
            return trim((string) ($t['title'] ?? '')) !== '';
        })->values();
    }

    private function mapSavedTendersToCollection(array $tenders)
    {
        return collect($tenders)->map(function ($t, $idx) {
            $documents = collect($t['documents'] ?? [])->map(function ($doc) {
                return [
                    'doc_label' => (string) ($doc['doc_label'] ?? ''),
                    'file_name' => (string) ($doc['file_name'] ?? ''),
                    'file_size' => (string) ($doc['file_size'] ?? ''),
                    'doc_url' => (string) ($doc['doc_url'] ?? ''),
                    's3_url' => (string) ($doc['s3_url'] ?? ''),
                    's3_key' => (string) ($doc['s3_key'] ?? ''),
                ];
            })->values()->toArray();

            $payloadRaw = $this->extractRawPayloadFromSavedRow($t);
            $payloadSource = is_array($payloadRaw['source_tender'] ?? null) ? $payloadRaw['source_tender'] : $payloadRaw;
            $location = (string) ($t['location'] ?? '');
            $sourceTenderId = $this->firstMeaningfulValue([
                $t['source_tender_id'] ?? '',
                $payloadSource['source_tender_id'] ?? '',
                $payloadSource['sourceTenderId'] ?? '',
            ]);
            $tenderNoticeNo = $this->firstMeaningfulValue([
                $t['tender_notice_no'] ?? '',
                $payloadSource['tender_notice_no'] ?? '',
                $payloadSource['tenderNoticeNo'] ?? '',
            ]);
            $tenderId = $this->firstMeaningfulValue([
                $t['tender_id'] ?? '',
                $sourceTenderId,
                $payloadSource['tenderId'] ?? '',
                $tenderNoticeNo,
            ]);
            $tenderNo = $this->firstMeaningfulValue([
                $t['tender_no'] ?? '',
                $tenderNoticeNo,
                $payloadSource['tenderNo'] ?? '',
                $sourceTenderId,
            ]);
            $authority = $this->firstMeaningfulValue([
                $t['authority'] ?? '',
                $t['tender_authority'] ?? '',
                $payloadSource['tender_authority'] ?? '',
                $payloadSource['tenderAuthority'] ?? '',
                $payloadSource['detail']['purchaser']['purchaserName'] ?? '',
                $payloadSource['purchaserName'] ?? '',
            ]);
            $purchaserAddress = $this->firstMeaningfulValue([
                $t['purchaser_address'] ?? '',
                $payloadSource['purchaser_address'] ?? '',
                $payloadSource['purchaserAddress'] ?? '',
                $payloadSource['detail']['purchaserAddress'] ?? '',
            ]);
            $website = $this->normalizeExternalUrl($this->firstMeaningfulValue([
                $t['website'] ?? '',
                $t['purchaser_url'] ?? '',
                $payloadSource['website'] ?? '',
                $payloadSource['purchaserUrl'] ?? '',
                $payloadSource['detail']['purchaser']['purchaserUrl'] ?? '',
            ]));
            $tenderUrl = $this->normalizeExternalUrl($this->firstMeaningfulValue([
                $t['tender_url'] ?? '',
                $t['source_url'] ?? '',
                $payloadSource['tender_url'] ?? '',
                $payloadSource['tenderUrl'] ?? '',
                $payloadSource['detail']['tenderUrl'] ?? '',
                $payloadSource['detailUrl'] ?? '',
                $website,
            ]));
            if ($tenderUrl === '' && str_contains(strtolower($website), 'tntenders.gov.in')) {
                $tenderUrl = 'https://tntenders.gov.in/nicgep/app?component=%24DirectLink&page=FrontEndViewTender&service=direct&session=T';
            }
            if ($website === '' && str_starts_with(strtolower($tenderUrl), 'http')) {
                $website = $tenderUrl;
            }
            return [
                'sl_no' => $idx + 1,
                'title' => (string) ($t['title'] ?? ''),
                'reference_no' => $tenderNo,
                'authority' => $authority,
                'location' => $location,
                'state' => $this->extractIndianState($location),
                'closing_date' => (string) ($t['closing_date'] ?? ''),
                'closing_label' => (string) ($t['closing_label'] ?? 'Closing Date'),
                'emd' => (string) ($t['emd'] ?? ''),
                'tender_value' => (string) ($t['tender_amount'] ?? ''),
                'type' => (string) ($t['type'] ?? ''),
                'services' => (string) ($t['type'] ?? ''),
                'category' => (string) ($t['category'] ?? 'Scraps'),
                'platform' => (string) ($t['platform'] ?? ''),
                'keyword' => (string) ($t['platform'] ?? ''),
                'description' => (string) ($t['description'] ?? ''),
                'url' => $tenderUrl,
                'phone_number' => (string) ($t['phone_number'] ?? ''),
                'opening_date' => (string) ($t['opening_date'] ?? ''),
                'tender_id' => $tenderId,
                'tender_no' => $tenderNo,
                'documents' => $documents,
                'major_source_data' => $this->buildMajorSourceData($this->resolveTenderSourcePayload([], $t)),
                'raw' => array_merge($t, [
                    'tender_authority' => $authority,
                    'purchaser_address' => $purchaserAddress,
                    'website' => $website,
                    'tender_url' => $tenderUrl,
                    'source_tender_id' => $sourceTenderId,
                    'tender_notice_no' => $tenderNoticeNo,
                ]),
            ];
        })->filter(function ($t) {
            return trim((string) ($t['title'] ?? '')) !== '';
        })->values();
    }

    private function indianStates(): array
    {
        return [
            'Andaman & Nicobar Islands',
            'Andhra Pradesh',
            'Arunachal Pradesh',
            'Assam',
            'Bihar',
            'Chandigarh',
            'Chhattisgarh',
            'Dadra & Nagar Haveli',
            'Daman & Diu',
            'Delhi',
            'Goa',
            'Gujarat',
            'Haryana',
            'Himachal Pradesh',
            'Jammu & Kashmir',
            'Jharkhand',
            'Karnataka',
            'Kerala',
            'Ladakh',
            'Lakshadweep',
            'Madhya Pradesh',
            'Maharashtra',
            'Manipur',
            'Meghalaya',
            'Mizoram',
            'Nagaland',
            'Odisha',
            'Pondicherry',
            'Punjab',
            'Rajasthan',
            'Sikkim',
            'Tamil Nadu',
            'Telangana',
            'Tripura',
            'Uttar Pradesh',
            'Uttarakhand',
            'West Bengal',
        ];
    }

    private function extractIndianState(string $location): string
    {
        $locationNorm = $this->normalizeStateString($location);
        foreach ($this->indianStates() as $state) {
            if (str_contains($locationNorm, $this->normalizeStateString($state))) {
                return $state;
            }
        }
        return 'Unknown';
    }

    private function normalizeStateString(string $value): string
    {
        $v = strtolower(trim($value));
        $v = str_replace(['&', ',', '.'], ['and', ' ', ' '], $v);
        $v = preg_replace('/\s+/', ' ', $v);
        return trim($v);
    }

    private function sanitizeSlug(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug);
        $slug = trim((string) $slug, '_');
        return $slug !== '' ? $slug : 'state';
    }

    private function formatMillisDate($value): string
    {
        if (!is_numeric($value)) {
            return '';
        }
        try {
            return \Carbon\Carbon::createFromTimestampMs((int) $value)->format('d M Y');
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function encodeV2LocalFilePath(string $path): string
    {
        return rtrim(strtr(base64_encode($path), '+/', '-_'), '=');
    }

    private function fetchStateTendersFromScrapeTenderScript(string $state, int $pageNumber = 0, string $bidassistAuthToken = ''): array
    {
        $pageNumber = max(0, $pageNumber);
        $scraperDir = $this->resolveScrapeTenderDir();
        $scriptPath = $scraperDir . '/code.js';
        $stateSafe = $this->sanitizeSlug($state);
        $prefix = 'bidassist_scraps_' . $stateSafe . '_page' . $pageNumber;
        $outputJson = $scraperDir . '/output/' . $stateSafe . '_page' . $pageNumber . '/' . $prefix . '.json';

        if (!file_exists($scriptPath)) {
            throw new \RuntimeException('scrapetender/code.js script not found');
        }

        if (file_exists($outputJson)) {
            @unlink($outputJson);
        }

        $env = array_merge($_ENV, [
            'BIDASSIST_PAGE_SIZE' => '10',
            'BIDASSIST_CATEGORY' => 'Scraps',
            'BIDASSIST_YEAR' => '2026',
        ]);
        if ($bidassistAuthToken !== '') {
            $env['BIDASSIST_AUTH_TOKEN'] = $bidassistAuthToken;
        }

        $process = new Process(['node', $scriptPath, $state, (string) $pageNumber], $scraperDir, $env);
        $process->setTimeout(360);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput() ?: 'Tender V2 scrape failed'));
        }

        if (!file_exists($outputJson)) {
            throw new \RuntimeException('Tender V2 output JSON not found');
        }

        $json = json_decode((string) file_get_contents($outputJson), true);
        if (!is_array($json)) {
            throw new \RuntimeException('Tender V2 output JSON invalid');
        }

        $items = collect($json['tenders'] ?? [])->map(function ($tender, $idx) use ($state, $pageNumber) {
            $title = trim((string) ($tender['displayPurchaserName'] ?? $tender['tenderDescription'] ?? ''));
            $sourceTenderId = trim((string) ($tender['sourceTenderId'] ?? ''));
            $tenderNoticeNo = trim((string) ($tender['tenderNoticeNo'] ?? ''));
            $tenderRef = trim((string) ($tenderNoticeNo ?: $sourceTenderId));
            $authority = trim((string) (($tender['detail']['purchaser']['purchaserName'] ?? '') ?: ($tender['purchaserName'] ?? '')));
            $purchaserAddress = trim((string) (($tender['detail']['purchaserAddress'] ?? '') ?: ($tender['purchaserAddress'] ?? '')));
            $website = trim((string) (($tender['detail']['purchaser']['purchaserUrl'] ?? '') ?: ($tender['purchaserUrl'] ?? '')));
            if ($website !== '' && !str_starts_with(strtolower($website), 'http://') && !str_starts_with(strtolower($website), 'https://')) {
                $website = 'https://' . ltrim($website, '/');
            }
            $website = rtrim($website, '/');
            $tenderUrl = trim((string) (($tender['detail']['tenderUrl'] ?? '') ?: ($tender['detailUrl'] ?? '') ?: $website));
            if ($tenderUrl !== '' && !str_starts_with(strtolower($tenderUrl), 'http://') && !str_starts_with(strtolower($tenderUrl), 'https://')) {
                $tenderUrl = 'https://' . ltrim($tenderUrl, '/');
            }
            if ($tenderUrl === '' && str_contains(strtolower($website), 'tntenders.gov.in')) {
                $tenderUrl = 'https://tntenders.gov.in/nicgep/app?component=%24DirectLink&page=FrontEndViewTender&service=direct&session=T';
            }
            $locationParts = array_filter([
                trim((string) ($tender['location']['location'] ?? '')),
                trim((string) ($tender['location']['district'] ?? '')),
                trim((string) ($tender['location']['state'] ?? '')),
            ]);
            $location = trim(implode(', ', $locationParts));
            $downloadedDocs = collect($tender['downloadedDocuments'] ?? [])->map(function ($doc) {
                $status = trim((string) ($doc['status'] ?? ''));
                $name = trim((string) (($doc['name'] ?? '') ?: ($doc['title'] ?? 'Document')));
                $savedTo = trim((string) ($doc['savedTo'] ?? ''));
                $downloadUrl = '';
                if ($status === 'downloaded' && $savedTo !== '') {
                    $downloadUrl = route('tenders.v2.document', ['p' => $this->encodeV2LocalFilePath($savedTo)]);
                }
                return [
                    'doc_label' => (string) ($doc['title'] ?? ($doc['name'] ?? 'Document')),
                    'file_name' => $name !== '' ? $name : 'Document',
                    'file_size' => $status !== '' ? strtoupper($status) : '',
                    'doc_url' => $downloadUrl,
                    's3_key' => '',
                    's3_url' => '',
                ];
            })->values();

            $detailDocs = collect($tender['detail']['documents'] ?? [])->map(function ($doc) {
                return [
                    'doc_label' => (string) ($doc['type'] ?? ''),
                    'file_name' => (string) ($doc['name'] ?? ($doc['title'] ?? 'Document')),
                    'file_size' => (string) ($doc['sizeString'] ?? ''),
                    'doc_url' => '',
                    's3_key' => '',
                    's3_url' => '',
                ];
            })->values();

            $docs = ($downloadedDocs->isNotEmpty() ? $downloadedDocs : $detailDocs)->toArray();

            return [
                'sl_no' => ($pageNumber * 10) + $idx + 1,
                'title' => $title !== '' ? $title : 'Tender',
                'reference_no' => $tenderRef,
                'authority' => $authority,
                'location' => $location,
                'state' => (string) ($tender['location']['state'] ?? $state),
                'closing_date' => $this->formatMillisDate($tender['bidDeadLine'] ?? null),
                'closing_label' => 'Closing Date',
                'emd' => (string) ($tender['emd'] ?? ''),
                'tender_value' => (string) ($tender['value'] ?? ''),
                'type' => (string) ($tender['typeOfContract'] ?? ''),
                'services' => (string) ($tender['typeOfContract'] ?? ''),
                'category' => (string) (($tender['sectorNames'][0] ?? '') ?: 'Scraps'),
                'platform' => (string) ($tender['procurementSource'] ?? ($tender['source'] ?? '')),
                'keyword' => (string) ($tender['source'] ?? ''),
                'description' => (string) ($tender['tenderDescription'] ?? ($tender['tenderDetails'] ?? '')),
                'url' => $tenderUrl,
                'phone_number' => '',
                'opening_date' => $this->formatMillisDate($tender['postingDate'] ?? null),
                // For Tender V2 card/modal, prefer notice/source id (e.g. LOT-13480-25-00008).
                'tender_id' => $sourceTenderId !== '' ? $sourceTenderId : ($tenderRef !== '' ? $tenderRef : (string) ($tender['tenderId'] ?? '')),
                'tender_no' => $tenderNoticeNo !== '' ? $tenderNoticeNo : ($sourceTenderId !== '' ? $sourceTenderId : (string) ($tender['tenderId'] ?? '')),
                'documents' => $docs,
                'raw' => array_merge($tender, [
                    'tender_authority' => $authority,
                    'purchaser_address' => $purchaserAddress,
                    'website' => $website,
                    'tender_url' => $tenderUrl,
                    'source_tender_id' => $sourceTenderId,
                    'tender_notice_no' => $tenderNoticeNo,
                ]),
            ];
        })->filter(function ($tender) {
            return trim((string) ($tender['title'] ?? '')) !== '';
        })->values();

        return [
            'items' => $items->toArray(),
            'total' => (int) ($json['pagination']['totalElements'] ?? $items->count()),
            'per_page' => (int) ($json['pagination']['pageSize'] ?? 10),
        ];
    }

    private function fetchStateTendersFromBidassistScraper(string $state, int $targetCount = 10, int $maxPages = 6, string $bidassistAuthToken = '')
    {
        $targetCount = max(10, min(60, $targetCount));
        $maxPages = max(1, min(6, $maxPages));
        $stateCacheKey = 'tenders_state_scrape_v7_' . md5($state . '|' . $targetCount . '|' . $maxPages . '|' . $bidassistAuthToken);
        $cached = Cache::get($stateCacheKey);
        if (is_array($cached) && !empty($cached)) {
            return collect($cached);
        }

        $repoRoot = dirname(base_path());
        $scraperDir = $repoRoot . '/bidassist-scraper';
        $scriptPath = $scraperDir . '/scrape-bidassist.js';
        $outputJson = $scraperDir . '/output/scrap-kerala-tenders.json';

        if (!file_exists($scriptPath)) {
            throw new \RuntimeException('bidassist-scraper script not found');
        }

        $allMapped = collect([]);
        $lastError = null;
        for ($pageNumber = 0; $pageNumber < $maxPages && $allMapped->count() < $targetCount; $pageNumber++) {
            try {
                $sourceUrl = $this->buildBidassistStateUrl($state, $pageNumber, 10);
                $env = array_merge($_ENV, [
                    'BIDASSIST_URL' => $sourceUrl,
                    'MAX_ITEMS' => '10',
                    'HEADLESS_MODE' => 'new',
                ]);
                if ($bidassistAuthToken !== '') {
                    $env['BIDASSIST_AUTH_TOKEN'] = $bidassistAuthToken;
                }

                $process = new Process(['node', $scriptPath], $scraperDir, $env);
                $process->setTimeout(240);
                $process->run();

                if (!$process->isSuccessful()) {
                    throw new \RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput() ?: 'State scrape failed'));
                }

                if (!file_exists($outputJson)) {
                    throw new \RuntimeException('State scraper output not found');
                }

                $json = json_decode((string) file_get_contents($outputJson), true);
                if (!is_array($json)) {
                    throw new \RuntimeException('Invalid JSON from state scraper');
                }

                $chunk = collect($json)->map(function ($t, $idx) use ($state, $pageNumber) {
                    return [
                        'sl_no' => ($pageNumber * 10) + $idx + 1,
                        'title' => (string) ($t['title'] ?? ''),
                        'reference_no' => (string) ($t['reference_no'] ?? ''),
                        'authority' => (string) ($t['authority'] ?? ''),
                        'location' => (string) ($t['location'] ?? ''),
                        'state' => $state,
                        'closing_date' => (string) ($t['closingDate'] ?? ($t['closing_date'] ?? '')),
                        'closing_label' => (string) ($t['status'] ?? ($t['closing_label'] ?? '')),
                        'emd' => (string) ($t['emd'] ?? ''),
                        'tender_value' => (string) ($t['tenderAmount'] ?? ($t['tender_value'] ?? '')),
                        'type' => (string) ($t['type'] ?? ''),
                        'services' => (string) ($t['type'] ?? ''),
                        'category' => (string) ($t['category'] ?? ''),
                        'platform' => (string) ($t['source'] ?? ($t['platform'] ?? '')),
                        'keyword' => (string) ($t['source'] ?? ($t['platform'] ?? '')),
                        'description' => (string) ($t['description'] ?? ''),
                        'url' => (string) ($t['url'] ?? ''),
                        'phone_number' => '',
                        'opening_date' => (string) ($t['openingDate'] ?? ''),
                        'tender_id' => (string) ($t['tender_id'] ?? ''),
                        'tender_no' => (string) ($t['tender_no'] ?? ''),
                        'documents' => is_array($t['documents'] ?? null) ? $t['documents'] : [],
                        'raw' => $t,
                    ];
                })->filter(function ($t) {
                    return trim((string) ($t['title'] ?? '')) !== '';
                })->values();

                $allMapped = $allMapped->concat($chunk)->values();
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::warning('State tender scrape page failed', [
                    'state' => $state,
                    'page_number' => $pageNumber,
                    'error' => $lastError,
                ]);
            }
        }

        if ($allMapped->isEmpty()) {
            throw new \RuntimeException($lastError ?: 'State scrape returned no data');
        }

        $mapped = $allMapped
            ->unique(function ($tender) {
                return $this->buildTenderHash((array) $tender);
            })
            ->values()
            ->take($targetCount);

        // Enrich listing output with saved contact/docs data when available.
        $mapped = $this->enrichFromSavedTenders($mapped);
        $mapped = $this->enrichMissingDetailsFromBidassistScraper($mapped);

        Cache::put($stateCacheKey, $mapped->toArray(), 60 * 10);
        return $mapped;
    }

    private function buildBidassistStateUrl(string $state, int $pageNumber = 0, int $pageSize = 10): string
    {
        $stateEncoded = rawurlencode($state);
        $pageNumber = max(0, $pageNumber);
        $pageSize = max(1, $pageSize);
        return "https://bidassist.com/all-tenders/active?filter=KEYWORD:scrap&filter=LOCATION_STRING:{$stateEncoded}&sort=RELEVANCE:DESC&pageNumber={$pageNumber}&pageSize={$pageSize}&tenderType=ACTIVE&tenderEntity=TENDER_LISTING&year=2026&removeUnavailableTenderAmountCards=false&removeUnavailableEmdCards=false";
    }

    private function enrichFromSavedTenders($tenders)
    {
        try {
            $savedResponse = $this->nodeApi->get('/accounts/tenders-saved', [], 120);
            $saved = collect($savedResponse['data']['tenders'] ?? []);
            if ($saved->isEmpty()) {
                return $tenders;
            }

            $savedBySourceUrl = [];
            foreach ($saved as $row) {
                $key = strtolower(trim((string) ($row['source_url'] ?? '')));
                if ($key !== '') {
                    $savedBySourceUrl[$key] = $row;
                }
            }

            return collect($tenders)->map(function ($tender) use ($savedBySourceUrl) {
                $sourceUrl = strtolower(trim((string) ($tender['url'] ?? '')));
                if ($sourceUrl === '' || !isset($savedBySourceUrl[$sourceUrl])) {
                    return $tender;
                }

                $saved = $savedBySourceUrl[$sourceUrl];
                $docs = collect($saved['documents'] ?? [])->map(function ($doc) {
                    return [
                        'doc_label' => (string) ($doc['doc_label'] ?? ''),
                        'file_name' => (string) ($doc['file_name'] ?? ''),
                        'file_size' => (string) ($doc['file_size'] ?? ''),
                        'doc_url' => (string) ($doc['doc_url'] ?? ''),
                        's3_key' => (string) ($doc['s3_key'] ?? ''),
                        's3_url' => (string) ($doc['s3_url'] ?? ''),
                    ];
                })->values()->toArray();

                $tender['opening_date'] = (string) ($saved['opening_date'] ?? ($tender['opening_date'] ?? ''));
                $tender['closing_date'] = (string) ($saved['closing_date'] ?? ($tender['closing_date'] ?? ''));
                $tender['closing_label'] = (string) ($saved['closing_label'] ?? ($tender['closing_label'] ?? ''));
                $tender['tender_value'] = (string) ($saved['tender_amount'] ?? ($tender['tender_value'] ?? ''));
                $tender['emd'] = (string) ($saved['emd'] ?? ($tender['emd'] ?? ''));
                $tender['description'] = (string) ($saved['description'] ?? ($tender['description'] ?? ''));

                $tender['tender_id'] = (string) ($saved['tender_id'] ?? '');
                $tender['tender_no'] = (string) ($saved['tender_no'] ?? '');
                $tender['authority'] = (string) ($saved['authority'] ?? ($saved['tender_authority'] ?? ($tender['authority'] ?? '')));
                $tender['raw']['tender_authority'] = (string) ($saved['tender_authority'] ?? '');
                $tender['raw']['purchaser_address'] = (string) ($saved['purchaser_address'] ?? '');
                $tender['raw']['website'] = (string) ($saved['website'] ?? '');
                $tender['raw']['summary'] = (string) ($saved['description'] ?? '');

                // Prefer actual Tender URL ("Click Here" target) when present.
                if (!empty($saved['tender_url'])) {
                    $tender['url'] = (string) $saved['tender_url'];
                }
                $tender['documents'] = $docs;

                return $tender;
            })->values();
        } catch (\Throwable $e) {
            Log::warning('Failed to enrich state tenders from saved details', ['error' => $e->getMessage()]);
            return $tenders;
        }
    }

    private function enrichMissingDetailsFromBidassistScraper($tenders)
    {
        return collect($tenders)->map(function ($tender) {
            $hasContact = trim((string) ($tender['tender_id'] ?? '')) !== ''
                || trim((string) ($tender['tender_no'] ?? '')) !== ''
                || trim((string) ($tender['raw']['tender_authority'] ?? '')) !== '';
            $hasDocs = !empty($tender['documents']) && is_array($tender['documents']);

            if ($hasContact && $hasDocs) {
                return $tender;
            }

            $detailUrl = trim((string) ($tender['raw']['source_url'] ?? $tender['url'] ?? ''));
            if ($detailUrl === '' || !str_contains($detailUrl, '/detail-')) {
                return $tender;
            }

            try {
                $detail = $this->fetchDetailFromBidassistScraper($detailUrl);
                if (!$detail) {
                    $detailMarkdown = $this->fetchViaJina($detailUrl);
                    if (trim($detailMarkdown) !== '') {
                        $parsed = $this->parseTenderDetailMarkdown($detailMarkdown, $tender);

                        if (trim((string) ($parsed['opening_date'] ?? '')) !== '') {
                            $tender['opening_date'] = (string) $parsed['opening_date'];
                        }
                        if (trim((string) ($parsed['closing_date'] ?? '')) !== '') {
                            $tender['closing_date'] = (string) $parsed['closing_date'];
                        }
                        if (trim((string) ($parsed['closing_label'] ?? '')) !== '') {
                            $tender['closing_label'] = (string) $parsed['closing_label'];
                        }
                        if (trim((string) ($parsed['tender_amount'] ?? '')) !== '') {
                            $tender['tender_value'] = (string) $parsed['tender_amount'];
                        }
                        if (trim((string) ($parsed['emd'] ?? '')) !== '') {
                            $tender['emd'] = (string) $parsed['emd'];
                        }
                        if (trim((string) ($parsed['tender_id'] ?? '')) !== '') {
                            $tender['tender_id'] = (string) $parsed['tender_id'];
                        }
                        if (trim((string) ($parsed['tender_no'] ?? '')) !== '') {
                            $tender['tender_no'] = (string) $parsed['tender_no'];
                        }
                        if (trim((string) ($parsed['tender_authority'] ?? '')) !== '') {
                            $tender['authority'] = (string) $parsed['tender_authority'];
                            $tender['raw']['tender_authority'] = (string) $parsed['tender_authority'];
                        }
                        if (trim((string) ($parsed['purchaser_address'] ?? '')) !== '') {
                            $tender['raw']['purchaser_address'] = (string) $parsed['purchaser_address'];
                        }
                        if (trim((string) ($parsed['website'] ?? '')) !== '') {
                            $tender['raw']['website'] = (string) $parsed['website'];
                        }
                        if (trim((string) ($parsed['description'] ?? '')) !== '') {
                            $tender['description'] = (string) $parsed['description'];
                        }
                        if (trim((string) ($parsed['url'] ?? '')) !== '' && str_starts_with(strtolower((string) $parsed['url']), 'http')) {
                            $tender['url'] = (string) $parsed['url'];
                            $tender['raw']['tender_url'] = (string) $parsed['url'];
                        }
                        if (!empty($parsed['documents']) && is_array($parsed['documents'])) {
                            $tender['documents'] = $parsed['documents'];
                        }
                    }
                    return $tender;
                }
                $tender = $this->mergeBidassistDetailIntoTender($tender, $detail);
            } catch (\Throwable $e) {
                Log::warning('Missing detail enrichment failed', [
                    'url' => $detailUrl,
                    'error' => $e->getMessage(),
                ]);
            }

            return $tender;
        })->values();
    }

    private function mergeBidassistDetailIntoTender(array $tender, array $detail): array
    {
        $openingDate = $this->firstMeaningfulValue([
            $detail['openingDate'] ?? '',
            $detail['auctionStartDate'] ?? '',
            $tender['opening_date'] ?? '',
        ]);
        $closingDate = $this->firstMeaningfulValue([
            $detail['closingDate'] ?? '',
            $detail['auctionEndDate'] ?? '',
            $tender['closing_date'] ?? '',
        ]);
        $tenderAmount = $this->firstMeaningfulValue([
            $detail['tenderAmount'] ?? '',
            $detail['tenderValue'] ?? '',
            $tender['tender_value'] ?? '',
        ]);

        $tender['opening_date'] = $openingDate;
        $tender['closing_date'] = $closingDate;
        $tender['closing_label'] = trim((string) ($detail['statusLabel'] ?? $tender['closing_label'] ?? ''));
        $tender['tender_value'] = $tenderAmount;

        $contact = $detail['contact'] ?? [];
        $costs = $detail['costs'] ?? [];
        $tender['tender_id'] = trim((string) ($contact['tenderId'] ?? $tender['tender_id'] ?? ''));
        $tender['tender_no'] = trim((string) ($contact['tenderNo'] ?? $tender['tender_no'] ?? ''));
        $tender['authority'] = trim((string) ($contact['tenderAuthority'] ?? $tender['authority'] ?? ''));
        $tender['emd'] = trim((string) ($costs['emd'] ?? $tender['emd'] ?? ''));
        $documentCost = $this->firstMeaningfulValue([
            $costs['documentCost'] ?? '',
            $costs['document_cost'] ?? '',
            $tender['raw']['document_cost'] ?? '',
            $tender['raw']['documentCost'] ?? '',
        ]);
        $tenderFee = $this->firstMeaningfulValue([
            $costs['tenderFee'] ?? '',
            $costs['tender_fee'] ?? '',
            $tender['raw']['tender_fee'] ?? '',
            $tender['raw']['tenderFee'] ?? '',
        ]);

        $tender['raw']['tender_authority'] = trim((string) ($contact['tenderAuthority'] ?? ($tender['raw']['tender_authority'] ?? '')));
        $tender['raw']['purchaser_address'] = trim((string) ($contact['purchaserAddress'] ?? ($tender['raw']['purchaser_address'] ?? '')));
        $tender['raw']['website'] = trim((string) ($contact['website'] ?? ($tender['raw']['website'] ?? '')));
        $tender['raw']['summary'] = trim((string) ($detail['summary'] ?? ($tender['raw']['summary'] ?? '')));
        $tender['raw']['document_cost'] = $documentCost;
        $tender['raw']['documentCost'] = $documentCost;
        $tender['raw']['tender_fee'] = $tenderFee;
        $tender['raw']['tenderFee'] = $tenderFee;
        $tender['raw']['tender_url'] = trim((string) ($contact['tenderUrl'] ?? ($tender['raw']['tender_url'] ?? '')));
        if (trim((string) ($tender['raw']['tender_url'] ?? '')) === '') {
            $websiteFallback = trim((string) ($tender['raw']['website'] ?? ''));
            if ($websiteFallback !== '' && str_starts_with(strtolower($websiteFallback), 'http')) {
                $tender['raw']['tender_url'] = $websiteFallback;
            }
        }

        // Prefer actual "Tender URL" ("Click Here" target) from detail contact panel.
        $resolvedTenderUrl = trim((string) ($contact['tenderUrl'] ?? ''));
        if ($resolvedTenderUrl !== '' && str_starts_with(strtolower($resolvedTenderUrl), 'http')) {
            $tender['url'] = $resolvedTenderUrl;
        }

        $docs = collect($detail['documents'] ?? [])->map(function ($doc) {
            return [
                'doc_label' => (string) ($doc['type'] ?? ''),
                'file_name' => (string) ($doc['fileName'] ?? ''),
                'file_size' => (string) ($doc['size'] ?? ''),
                'doc_url' => (string) ($doc['docUrl'] ?? ''),
                's3_key' => '',
                's3_url' => '',
            ];
        })->filter(function ($doc) {
            return trim((string) ($doc['file_name'] ?? '')) !== '';
        })->values()->toArray();

        if (!empty($docs)) {
            $tender['documents'] = $this->applyDocumentUrlFallbacks(
                $docs,
                (string) (($tender['raw']['tender_url'] ?? '') ?: ($tender['url'] ?? '') ?: ($tender['raw']['website'] ?? ''))
            );
        }

        return $tender;
    }

    private function isMeaningfulValue($value): bool
    {
        $v = strtolower(trim((string) $value));
        if ($v === '') return false;
        if (in_array($v, ['n/a', 'na', '-', '--', 'null', 'none', 'unknown'], true)) {
            return false;
        }
        return true;
    }

    private function firstMeaningfulValue(array $values): string
    {
        foreach ($values as $value) {
            if ($this->isMeaningfulValue($value)) {
                return trim((string) $value);
            }
        }
        return '';
    }

    private function normalizeExternalUrl(string $value): string
    {
        $value = trim($value);
        if (!$this->isMeaningfulValue($value)) {
            return '';
        }
        if (!str_starts_with(strtolower($value), 'http://') && !str_starts_with(strtolower($value), 'https://')) {
            $value = 'https://' . ltrim($value, '/');
        }
        return rtrim($value, '/');
    }

    private function extractRawPayloadFromSavedRow(array $row): array
    {
        $decoded = json_decode((string) ($row['raw_payload'] ?? ''), true);
        if (!is_array($decoded)) {
            return [];
        }
        $raw = $decoded['raw'] ?? [];
        return is_array($raw) ? $raw : [];
    }

    private function resolveTenderSourcePayload(array $tender = [], array $savedRow = []): array
    {
        if (!empty($savedRow)) {
            $payloadRaw = $this->extractRawPayloadFromSavedRow($savedRow);
            if (is_array($payloadRaw['source_tender'] ?? null)) {
                return $payloadRaw['source_tender'];
            }
            if (!empty($payloadRaw)) {
                return $payloadRaw;
            }
            return $this->synthesizeSourcePayloadFromSavedLike($savedRow);
        }

        $raw = is_array($tender['raw'] ?? null) ? $tender['raw'] : [];
        if (is_array($raw['source_tender'] ?? null)) {
            return $raw['source_tender'];
        }
        if (!empty($raw)) {
            return $raw;
        }
        return $this->synthesizeSourcePayloadFromSavedLike($tender);
    }

    private function synthesizeSourcePayloadFromSavedLike(array $row): array
    {
        $documents = is_array($row['documents'] ?? null) ? $row['documents'] : [];
        $locationRaw = trim((string) ($row['location'] ?? ''));
        $district = '';
        $state = '';
        if ($locationRaw !== '') {
            $parts = array_values(array_filter(array_map('trim', explode(',', $locationRaw))));
            if (count($parts) > 0) $district = $parts[count($parts) - 2] ?? ($parts[count($parts) - 1] ?? '');
            if (count($parts) > 1) $state = $parts[count($parts) - 1] ?? '';
        }

        return [
            'sourceTenderId' => (string) (($row['source_tender_id'] ?? '') ?: ($row['tender_id'] ?? '')),
            'tenderId' => (string) ($row['tender_id'] ?? ''),
            'tenderNoticeNo' => (string) (($row['tender_notice_no'] ?? '') ?: ($row['tender_no'] ?? '')),
            'tenderDescription' => (string) (($row['description'] ?? '') ?: ($row['title'] ?? '')),
            'translatedTenderDescription' => null,
            'workflowStatus' => 'PUBLISHED',
            'typeOfContract' => (string) ($row['type'] ?? ''),
            'procurementSource' => (string) ($row['platform'] ?? ''),
            'detailUrl' => (string) (($row['source_url'] ?? '') ?: ($row['tender_url'] ?? '')),
            'source' => (string) ($row['source'] ?? 'OFB'),
            'emd' => (string) ($row['emd'] ?? ''),
            'emdInUsd' => null,
            'value' => (string) ($row['tender_amount'] ?? ''),
            'valueInUsd' => null,
            'currency' => 'INR',
            'tenderFee' => null,
            'documentCost' => null,
            'postingDate' => null,
            'bidDeadLine' => null,
            'purchaserName' => (string) (($row['authority'] ?? '') ?: ($row['tender_authority'] ?? '')),
            'displayPurchaserName' => (string) ($row['title'] ?? ''),
            'purchaserId' => '',
            'purchaserUrl' => (string) ($row['website'] ?? ''),
            'purchaserAddress' => (string) ($row['purchaser_address'] ?? ''),
            'purchaserEmail' => '',
            'location' => [
                'location' => $locationRaw,
                'district' => $district,
                'state' => $state,
                'taluk' => '',
                'pincode' => '',
                'country' => ['name' => 'India'],
                'zone' => '',
            ],
            'sectorNames' => is_array($row['sectorNames'] ?? null) ? $row['sectorNames'] : ['Scraps'],
            'documents' => $documents,
            'downloadedDocuments' => $documents,
            'detail' => [
                'documents' => $documents,
                'boqItems' => [],
                'relatedTenderDetailDTOS' => [],
                'location' => [
                    'location' => $locationRaw,
                    'district' => $district,
                    'state' => $state,
                    'taluk' => '',
                    'pincode' => '',
                    'country' => ['name' => 'India'],
                    'zone' => '',
                ],
                'purchaser' => [
                    'purchaserName' => (string) (($row['authority'] ?? '') ?: ($row['tender_authority'] ?? '')),
                    'purchaserUrl' => (string) ($row['website'] ?? ''),
                    'organisationChain' => '',
                    'purchaserOwnership' => '',
                ],
                'procurementSource' => (string) ($row['platform'] ?? ''),
                'typeOfContract' => (string) ($row['type'] ?? ''),
                'currency' => ['currency' => 'INR'],
            ],
            'downloadedDocumentsCount' => count($documents),
            'documentCount' => count($documents),
            'viewCount' => null,
            'saved' => null,
            'viewed' => null,
            'savedLabels' => null,
            'userNote' => null,
            'reviewed' => null,
            'isAIEnabled' => null,
            'scoreData' => null,
            'updatedFields' => null,
            'latestChangedTenderField' => '',
            'estimatedValuePresent' => null,
            'detailPageInfo' => [
                'pageName' => 'detail',
                'tenderEntity' => 'TENDER',
            ],
        ];
    }

    private function buildMajorSourceData(array $source): array
    {
        if (empty($source)) {
            return [];
        }
        $detailDocs = collect($source['detail']['documents'] ?? [])->map(function ($doc) {
            return [
                'name' => (string) ($doc['name'] ?? ''),
                'cipherDocumentId' => (string) ($doc['cipherDocumentId'] ?? ''),
                'documentId' => $doc['documentId'] ?? null,
                's3Key' => (string) ($doc['s3Key'] ?? ''),
                'status' => (string) ($doc['status'] ?? ''),
                'size' => $doc['size'] ?? null,
                'sizeString' => (string) ($doc['sizeString'] ?? ''),
                'title' => (string) ($doc['title'] ?? ''),
                'type' => (string) ($doc['type'] ?? ''),
                'description' => (string) ($doc['description'] ?? ''),
            ];
        })->values()->toArray();
        $downloadedDocs = collect($source['downloadedDocuments'] ?? [])->map(function ($doc) {
            return [
                'name' => (string) ($doc['name'] ?? ''),
                'title' => (string) ($doc['title'] ?? ''),
                'documentId' => $doc['documentId'] ?? null,
                'cipherDocumentId' => (string) ($doc['cipherDocumentId'] ?? ''),
                'status' => (string) ($doc['status'] ?? ''),
                'usedId' => (string) ($doc['usedId'] ?? ''),
                'savedTo' => (string) ($doc['savedTo'] ?? ''),
            ];
        })->values()->toArray();
        $location = is_array($source['location'] ?? null) ? $source['location'] : [];
        $detailLocation = is_array($source['detail']['location'] ?? null) ? $source['detail']['location'] : [];
        $detail = is_array($source['detail'] ?? null) ? $source['detail'] : [];
        $detailPurchaser = is_array($detail['purchaser'] ?? null) ? $detail['purchaser'] : [];
        $detailCurrency = is_array($detail['currency'] ?? null) ? $detail['currency'] : [];
        $detailLocationCountry = is_array($detailLocation['country'] ?? null) ? $detailLocation['country'] : [];
        $boqItems = is_array($detail['boqItems'] ?? null) ? $detail['boqItems'] : [];
        $relatedTenders = is_array($detail['relatedTenderDetailDTOS'] ?? null) ? $detail['relatedTenderDetailDTOS'] : [];
        $bidDeadlineMs = $source['bidDeadLine'] ?? ($source['bidDeadline'] ?? ($detail['bidDeadline'] ?? null));
        $postingDateMs = $source['postingDate'] ?? ($detail['postingDate'] ?? null);

        return [
            '1_basic_tender_information' => [
                'tenderId' => (string) ($source['tenderId'] ?? ($detail['tenderId'] ?? '')),
                'sourceTenderId' => (string) ($source['sourceTenderId'] ?? ($source['source_tender_id'] ?? ($detail['sourceTenderId'] ?? ''))),
                'tenderNoticeNo' => (string) ($source['tenderNoticeNo'] ?? ($source['tender_notice_no'] ?? ($detail['tenderNoticeNo'] ?? ''))),
                'tenderDescription' => (string) ($source['tenderDescription'] ?? ($source['tenderDetails'] ?? ($detail['tenderDescription'] ?? ''))),
                'translatedTenderDescription' => $source['translatedTenderDescription'] ?? ($detail['translatedTenderDescription'] ?? null),
                'workflowStatus' => (string) ($source['workflowStatus'] ?? ($detail['workflowStatus'] ?? '')),
                'typeOfContract' => (string) (($source['typeOfContract'] ?? '') ?: ($detail['typeOfContract'] ?? '')),
                'procurementSource' => (string) (($source['procurementSource'] ?? '') ?: ($detail['procurementSource'] ?? '')),
                'detailUrl' => (string) ($source['detailUrl'] ?? ''),
                'source' => (string) ($source['source'] ?? ($detail['source'] ?? '')),
            ],
            '2_financial_information' => [
                'emd' => $source['emd'] ?? ($detail['emd'] ?? null),
                'emdInUsd' => $source['emdInUsd'] ?? ($detail['emdInUsd'] ?? null),
                'value' => $source['value'] ?? ($detail['value'] ?? null),
                'valueInUsd' => $source['valueInUsd'] ?? ($detail['valueInUsd'] ?? null),
                'currency' => (string) (($source['currency'] ?? '') ?: ($detailCurrency['currency'] ?? '')),
                'tenderFee' => $source['tenderFee'] ?? ($detail['tenderFee'] ?? null),
                'documentCost' => $source['documentCost'] ?? ($detail['documentCost'] ?? null),
            ],
            '3_important_dates' => [
                'postingDate' => $postingDateMs,
                'bidDeadline' => $bidDeadlineMs,
                'auctionStartDate' => $detail['auctionStartDate'] ?? null,
                'auctionEndDate' => $detail['auctionEndDate'] ?? null,
                'dateCreated' => $source['dateCreated'] ?? ($detail['dateCreated'] ?? null),
                'dateModified' => $source['dateModified'] ?? ($detail['dateModified'] ?? null),
            ],
            '4_purchaser_authority_information' => [
                'purchaserName' => (string) ($source['purchaserName'] ?? ($detailPurchaser['purchaserName'] ?? '')),
                'displayPurchaserName' => (string) ($source['displayPurchaserName'] ?? ($detailPurchaser['displayPurchaserName'] ?? '')),
                'purchaserId' => (string) ($source['purchaserId'] ?? ($detailPurchaser['purchaserId'] ?? '')),
                'purchaserUrl' => (string) (($source['purchaserUrl'] ?? '') ?: ($detailPurchaser['purchaserUrl'] ?? '')),
                'purchaserAddress' => (string) (($source['purchaserAddress'] ?? '') ?: ($detail['purchaserAddress'] ?? '')),
                'purchaserEmail' => (string) ($source['purchaserEmail'] ?? ''),
                'organisationChain' => (string) ($detailPurchaser['organisationChain'] ?? ''),
                'purchaserOwnership' => (string) ($detailPurchaser['purchaserOwnership'] ?? ''),
            ],
            '5_location_information' => [
                'pincodeDetailId' => (string) (($location['pincodeDetailId'] ?? '') ?: ($detailLocation['pincodeDetailId'] ?? '')),
                'location' => (string) (($location['location'] ?? '') ?: ($detailLocation['location'] ?? '')),
                'pincode' => (string) (($location['pincode'] ?? '') ?: ($detailLocation['pincode'] ?? '')),
                'taluk' => (string) (($location['taluk'] ?? '') ?: ($detailLocation['taluk'] ?? '')),
                'district' => (string) (($location['district'] ?? '') ?: ($detailLocation['district'] ?? '')),
                'state' => (string) (($location['state'] ?? '') ?: ($detailLocation['state'] ?? '')),
                'zone' => (string) (($location['zone'] ?? '') ?: ($detailLocation['zone'] ?? '')),
                'countryCode' => (string) (($location['country']['country'] ?? '') ?: ($detailLocationCountry['country'] ?? '')),
                'country' => (string) (($location['country']['name'] ?? '') ?: ($detailLocationCountry['name'] ?? '')),
                'locationSource' => (string) (($location['locationSource'] ?? '') ?: ($detailLocation['locationSource'] ?? '')),
            ],
            '6_sector_category' => [
                'sectorNames' => is_array($source['sectorNames'] ?? null) ? $source['sectorNames'] : (is_array($detail['sectorNames'] ?? null) ? $detail['sectorNames'] : []),
                'sectors' => is_array($detail['sectors'] ?? null) ? $detail['sectors'] : [],
            ],
            '7_documents' => [
                'documentCount' => (int) ($source['documentCount'] ?? count($detailDocs)),
                'documents' => $detailDocs,
            ],
            '8_boq_items' => [
                'boqItemsCount' => count($boqItems),
                'boqItems' => $boqItems,
            ],
            '9_user_activity_fields' => [
                'saved' => $source['saved'] ?? ($detail['saved'] ?? null),
                'viewed' => $source['viewed'] ?? ($detail['viewed'] ?? null),
                'viewCount' => $source['viewCount'] ?? ($detail['viewCount'] ?? null),
                'savedLabels' => $source['savedLabels'] ?? ($detail['savedLabels'] ?? null),
                'userNote' => $source['userNote'] ?? ($detail['userNote'] ?? null),
            ],
            '10_related_tenders' => [
                'relatedTenderDetailDTOSCount' => count($relatedTenders),
                'relatedTenderDetailDTOS' => $relatedTenders,
            ],
            '11_document_download_information' => [
                'downloadedDocumentsCount' => count($downloadedDocs),
                'downloadedDocuments' => $downloadedDocs,
            ],
            '12_tender_metadata' => [
                'reviewed' => $source['reviewed'] ?? ($detail['reviewed'] ?? null),
                'isAIEnabled' => $source['isAIEnabled'] ?? null,
                'scoreData' => $source['scoreData'] ?? null,
                'updatedFields' => $source['updatedFields'] ?? ($detail['updatedFields'] ?? null),
                'latestChangedTenderField' => (string) ($source['latestChangedTenderField'] ?? ''),
                'estimatedValuePresent' => $source['estimatedValuePresent'] ?? ($detail['estimatedValuePresent'] ?? null),
            ],
            'detailPageInfo' => is_array($source['detailPageInfo'] ?? null) ? $source['detailPageInfo'] : [],
            'raw_counts' => [
                'detailDocumentsCount' => count($detailDocs),
                'boqItemsCount' => count($boqItems),
                'relatedTendersCount' => count($relatedTenders),
                'downloadedDocumentsCount' => count($downloadedDocs),
            ],
        ];
    }

    private function applyDocumentUrlFallbacks(array $docs, string $fallbackUrl): array
    {
        $fallback = trim($fallbackUrl);
        return collect($docs)->map(function ($doc) use ($fallback) {
            $current = trim((string) ($doc['doc_url'] ?? ''));
            if ($current === '' && $fallback !== '' && str_starts_with(strtolower($fallback), 'http')) {
                $doc['doc_url'] = $fallback;
            }
            return $doc;
        })->toArray();
    }

    private function fetchDetailFromBidassistScraper(string $detailUrl, array &$debug = [], string $bidassistAuthToken = ''): ?array
    {
        $repoRoot = dirname(base_path());
        $scraperDir = $repoRoot . '/bidassist-scraper';
        $scriptPath = $scraperDir . '/scrape-bidassist-detail.js';
        $outputJson = $scraperDir . '/output/tender-detail.json';

        if (!file_exists($scriptPath)) {
            return null;
        }

        $env = array_merge($_ENV, [
            'BIDASSIST_DETAIL_URL' => $detailUrl,
            'HEADLESS_MODE' => 'new',
        ]);
        if ($bidassistAuthToken !== '') {
            $env['BIDASSIST_AUTH_TOKEN'] = $bidassistAuthToken;
        }

        // Expected runtime command per card click:
        // node /Users/shijo/Documents/GitHub/flutternode/bidassist-scraper/scrape-bidassist-detail.js "DETAIL_URL"
        Log::info('Running bidassist detail scraper command', [
            'cmd' => ['node', $scriptPath, $detailUrl],
            'cwd' => $scraperDir,
            'detail_url' => $detailUrl,
        ]);
        $debug[] = 'Exec: node ' . $scriptPath . ' "' . $detailUrl . '"';

        // Avoid stale reads from previous runs.
        if (file_exists($outputJson)) {
            @unlink($outputJson);
            $debug[] = 'Cleared previous output JSON';
        }

        $process = new Process(['node', $scriptPath, $detailUrl], $scraperDir, $env);
        $process->setTimeout(180);
        $process->run();
        $debug[] = 'Exit code: ' . (string) $process->getExitCode();
        $stdout = trim((string) $process->getOutput());
        $stderr = trim((string) $process->getErrorOutput());
        if ($stdout !== '') {
            $debug[] = 'stdout: ' . mb_substr($stdout, 0, 240);
        }
        if ($stderr !== '') {
            $debug[] = 'stderr: ' . mb_substr($stderr, 0, 240);
        }

        if (!$process->isSuccessful()) {
            // Some scraper failures still emit usable output JSON; accept only if URL matches requested detail URL.
            if (file_exists($outputJson)) {
                $jsonOnFailure = json_decode((string) file_get_contents($outputJson), true);
                $jsonUrl = strtolower(trim((string) ($jsonOnFailure['url'] ?? '')));
                $reqUrl = strtolower(trim($detailUrl));
                if (is_array($jsonOnFailure) && $jsonUrl !== '' && $jsonUrl === $reqUrl) {
                    Log::warning('Bidassist detail scraper exited non-zero but returned matching JSON output', [
                        'detail_url' => $detailUrl,
                        'exit_code' => $process->getExitCode(),
                    ]);
                    $debug[] = 'Using JSON output despite non-zero exit (URL matched)';
                    return $jsonOnFailure;
                }
            }
            Log::warning('Bidassist detail scraper process failed', [
                'detail_url' => $detailUrl,
                'exit_code' => $process->getExitCode(),
                'stdout' => trim((string) $process->getOutput()),
                'stderr' => trim((string) $process->getErrorOutput()),
                'output_exists' => file_exists($outputJson),
            ]);
            return null;
        }

        if (!file_exists($outputJson)) {
            Log::warning('Bidassist detail scraper completed but output JSON missing', [
                'detail_url' => $detailUrl,
            ]);
            $debug[] = 'Output JSON missing';
            return null;
        }

        $json = json_decode((string) file_get_contents($outputJson), true);
        if (!is_array($json)) {
            $debug[] = 'Output JSON parse failed';
            return null;
        }
        $jsonUrl = strtolower(trim((string) ($json['url'] ?? '')));
        $reqUrl = strtolower(trim($detailUrl));
        if ($jsonUrl !== '' && $jsonUrl !== $reqUrl) {
            Log::warning('Bidassist detail scraper output URL mismatch', [
                'detail_url' => $detailUrl,
                'json_url' => $json['url'] ?? '',
            ]);
            $debug[] = 'Output JSON URL mismatch';
            return null;
        }
        $debug[] = 'Output JSON accepted';
        return $json;
    }

    private function uploadTenderDocumentsToS3(array $tender, array &$trace): array
    {
        $docs = is_array($tender['documents'] ?? null) ? $tender['documents'] : [];
        if (empty($docs)) {
            $trace[] = 'No documents to upload to S3';
            return $tender;
        }

        $awsKey = trim((string) env('AWS_ACCESS_KEY_ID', ''));
        $awsSecret = trim((string) env('AWS_SECRET_ACCESS_KEY', ''));
        $awsRegion = trim((string) env('AWS_DEFAULT_REGION', ''));
        $awsBucket = trim((string) env('AWS_BUCKET', ''));
        $missingAwsConfig = [];
        if ($awsKey === '') {
            $missingAwsConfig[] = 'AWS_ACCESS_KEY_ID';
        }
        if ($awsSecret === '') {
            $missingAwsConfig[] = 'AWS_SECRET_ACCESS_KEY';
        }
        if ($awsRegion === '') {
            $missingAwsConfig[] = 'AWS_DEFAULT_REGION';
        }
        if ($awsBucket === '') {
            $missingAwsConfig[] = 'AWS_BUCKET';
        }
        if (!empty($missingAwsConfig)) {
            $trace[] = 'S3 upload skipped (missing AWS config): ' . implode(', ', $missingAwsConfig);
            return $tender;
        }

        $tenderId = trim((string) ($tender['tender_id'] ?? ''));
        $baseFolder = 'tenders/documents/' . date('Y/m/d') . '/' . ($tenderId !== '' ? $this->safeFileSegment($tenderId) : 'unknown');
        try {
            $disk = Storage::disk('s3');
        } catch (\Throwable $e) {
            // Missing Flysystem S3 adapter should not block detail refresh.
            Log::warning('S3 disk unavailable while uploading tender documents', [
                'tender_id' => $tenderId,
                'error' => $e->getMessage(),
            ]);
            $trace[] = 'S3 disk unavailable; skipped S3 upload: ' . $e->getMessage();
            return $tender;
        }
        $uploadedCount = 0;

        foreach ($docs as $idx => $doc) {
            $docUrl = trim((string) ($doc['doc_url'] ?? ''));
            if ($docUrl === '') {
                continue;
            }
            if (str_starts_with(strtolower($docUrl), 'http')) {
                $docs[$idx]['s3_url'] = trim((string) ($doc['s3_url'] ?? $docUrl));
                $docs[$idx]['s3_key'] = trim((string) ($doc['s3_key'] ?? ''));
                continue;
            }

            $localPath = $this->resolveLocalDocumentPath($docUrl);
            if (!$localPath || !is_file($localPath)) {
                $trace[] = 'S3 upload skipped (missing local file): ' . $docUrl;
                continue;
            }

            $baseName = trim((string) ($doc['file_name'] ?? ''));
            if ($baseName === '') {
                $baseName = basename($localPath);
            }
            $safeName = $this->safeFileSegment($baseName);
            if ($safeName === '') {
                $safeName = 'document_' . ($idx + 1);
            }
            $s3Key = $baseFolder . '/' . $safeName;

            try {
                $content = @file_get_contents($localPath);
                if ($content === false) {
                    $trace[] = 'S3 upload read failed: ' . $localPath;
                    continue;
                }

                $mime = @mime_content_type($localPath) ?: 'application/octet-stream';
                // Some buckets disable ACLs/Object ACL APIs. Try with public visibility first,
                // then retry without ACL metadata if the adapter returns false.
                $ok = $disk->put($s3Key, $content, [
                    'visibility' => 'public',
                    'ContentType' => $mime,
                ]);
                if (!$ok) {
                    $trace[] = 'S3 upload retry without ACL: ' . $s3Key;
                    $ok = $disk->put($s3Key, $content, [
                        'ContentType' => $mime,
                    ]);
                }
                if (!$ok) {
                    Log::warning('S3 upload failed (put returned false)', [
                        's3_key' => $s3Key,
                        'local_path' => $localPath,
                        'mime' => $mime,
                        'bucket' => $awsBucket,
                        'region' => $awsRegion,
                        'disk_throw' => (bool) config('filesystems.disks.s3.throw', false),
                    ]);
                    $trace[] = 'S3 upload failed: ' . $s3Key . ' (put returned false; check AWS credentials/bucket/permissions)';
                    continue;
                }

                $s3Url = $disk->url($s3Key);
                $docs[$idx]['s3_key'] = $s3Key;
                $docs[$idx]['s3_url'] = $s3Url;
                $docs[$idx]['doc_url'] = $s3Url;
                $uploadedCount++;
            } catch (\Throwable $e) {
                $trace[] = 'S3 upload exception: ' . $e->getMessage();
            }
        }

        $trace[] = 'Documents uploaded to S3: ' . $uploadedCount;
        $tender['documents'] = $docs;
        return $tender;
    }

    private function resolveLocalDocumentPath(string $docUrl): ?string
    {
        $u = trim($docUrl);
        if ($u === '') {
            return null;
        }

        if (str_starts_with(strtolower($u), 'file://')) {
            $p = parse_url($u, PHP_URL_PATH);
            if (!is_string($p) || trim($p) === '') {
                return null;
            }
            return urldecode($p);
        }

        if (str_starts_with($u, '/')) {
            return $u;
        }

        return null;
    }

    private function safeFileSegment(string $value): string
    {
        $out = preg_replace('/[^A-Za-z0-9._-]+/', '_', trim($value));
        return trim((string) $out, '._-');
    }

    private function syncSingleTenderToAws(array $tender, string $detailUrl, array &$trace): void
    {
        try {
            $docs = collect($tender['documents'] ?? [])->map(function ($doc) {
                return [
                    'doc_label' => (string) ($doc['doc_label'] ?? ''),
                    'file_name' => (string) ($doc['file_name'] ?? ''),
                    'file_size' => (string) ($doc['file_size'] ?? ''),
                    'doc_url' => (string) (($doc['s3_url'] ?? '') ?: ($doc['doc_url'] ?? '')),
                    's3_key' => (string) ($doc['s3_key'] ?? ''),
                    's3_url' => (string) ($doc['s3_url'] ?? ''),
                ];
            })->toArray();

            $payloadTender = [
                'url' => (string) ($tender['url'] ?? $detailUrl),
                'source_url' => (string) ($tender['url'] ?? $detailUrl),
                'title' => (string) ($tender['title'] ?? ''),
                'authority' => (string) ($tender['authority'] ?? ''),
                'location' => (string) ($tender['location'] ?? ''),
                'description' => (string) ($tender['description'] ?? ''),
                'type' => (string) ($tender['type'] ?? ''),
                'category' => (string) ($tender['category'] ?? ''),
                'platform' => (string) ($tender['platform'] ?? ''),
                'opening_date' => (string) ($tender['opening_date'] ?? ''),
                'closing_date' => (string) ($tender['closing_date'] ?? ''),
                'closing_label' => (string) ($tender['closing_label'] ?? ''),
                'tender_value' => (string) ($tender['tender_value'] ?? ''),
                'tender_amount' => (string) ($tender['tender_value'] ?? ''),
                'emd' => (string) ($tender['emd'] ?? ''),
                'tender_id' => (string) ($tender['tender_id'] ?? ''),
                'tender_no' => (string) ($tender['tender_no'] ?? ''),
                'tender_authority' => (string) ($tender['raw']['tender_authority'] ?? ($tender['authority'] ?? '')),
                'purchaser_address' => (string) ($tender['raw']['purchaser_address'] ?? ''),
                'website' => (string) ($tender['raw']['website'] ?? ''),
                'tender_url' => (string) (($tender['raw']['tender_url'] ?? '') ?: ($tender['url'] ?? $detailUrl)),
                'documents' => $docs,
                'raw_payload' => json_encode([
                    'detail_url' => $detailUrl,
                    'scraped_at' => now()->toIso8601String(),
                    'tender' => $tender,
                ]),
            ];

            $syncPayload = [
                'source_list_url' => 'manual-card-refresh',
                'tenders' => [$payloadTender],
            ];

            $syncResponse = $this->nodeApi->post('/accounts/tenders-sync', $syncPayload, 180);
            if (($syncResponse['status'] ?? 'error') === 'success') {
                $saved = (int) (($syncResponse['data']['saved_tenders'] ?? 0));
                $skipped = (int) (($syncResponse['data']['skipped_tenders'] ?? 0));
                $trace[] = 'Synced to AWS (saved=' . $saved . ', skipped=' . $skipped . ')';
                return;
            }

            $trace[] = 'AWS sync failed: ' . (string) ($syncResponse['msg'] ?? 'unknown error');
        } catch (\Throwable $e) {
            $trace[] = 'AWS sync exception: ' . $e->getMessage();
        }
    }

    private function defaultBidAssistUrl()
    {
        return 'https://bidassist.com/all-tenders/active?filter=CATEGORY:Scraps&filter=LOCATION_STRING:Kerala&sort=RELEVANCE:DESC&pageNumber=0&pageSize=10&tenderType=ACTIVE&tenderEntity=TENDER_LISTING&year=2026&removeUnavailableTenderAmountCards=false&removeUnavailableEmdCards=false';
    }

    private function canonicalBidAssistUrl(string $incoming = '')
    {
        $default = $this->defaultBidAssistUrl();
        // Always enforce exact URL requested by product flow.
        return $default;
    }

    private function parseTenderPayload(string $body)
    {
        $decoded = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $items = $this->findTenderItemsInTree($decoded);
            if (!empty($items)) {
                return ['mode' => 'json', 'items' => $items];
            }
        }

        if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $body, $m)) {
            $nextData = json_decode($m[1], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($nextData)) {
                $items = $this->findTenderItemsInTree($nextData);
                if (!empty($items)) {
                    return ['mode' => '__NEXT_DATA__', 'items' => $items];
                }
            }
        }

        return ['mode' => 'unparsed', 'items' => []];
    }

    private function findTenderItemsInTree($node)
    {
        if (!is_array($node)) {
            return [];
        }

        // Direct known keys first
        foreach (['tenders', 'tenderList', 'tender_listing', 'results', 'items', 'data'] as $key) {
            if (isset($node[$key]) && is_array($node[$key]) && $this->looksLikeTenderList($node[$key])) {
                return $node[$key];
            }
        }

        // Recursive search
        foreach ($node as $value) {
            if (is_array($value)) {
                if ($this->looksLikeTenderList($value)) {
                    return $value;
                }
                $nested = $this->findTenderItemsInTree($value);
                if (!empty($nested)) {
                    return $nested;
                }
            }
        }

        return [];
    }

    private function looksLikeTenderList(array $arr)
    {
        if (empty($arr)) {
            return false;
        }

        $first = reset($arr);
        if (!is_array($first)) {
            return false;
        }

        $known = [
            'title', 'tender_title', 'tenderTitle',
            'reference_no', 'referenceNo', 'tenderNo',
            'authority', 'organisation', 'organization',
            'submission_end_date', 'closing_date', 'closingDate',
        ];

        foreach ($known as $k) {
            if (array_key_exists($k, $first)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeTender(array $item, int $serial)
    {
        $title = $item['title'] ?? $item['tender_title'] ?? $item['tenderTitle'] ?? $item['name'] ?? '';
        $referenceNo = $item['reference_no'] ?? $item['referenceNo'] ?? $item['tenderNo'] ?? $item['tender_no'] ?? '';
        $authority = $item['authority'] ?? $item['organisation'] ?? $item['organization'] ?? $item['department'] ?? '';
        $location = $item['location'] ?? $item['city'] ?? $item['state'] ?? '';
        $closingDate = $item['submission_end_date'] ?? $item['closing_date'] ?? $item['closingDate'] ?? $item['bid_end_date'] ?? '';
        $emd = $item['emd_amount'] ?? $item['emd'] ?? $item['earnest_money'] ?? '';
        $value = $item['tender_value'] ?? $item['value'] ?? $item['estimate_value'] ?? '';
        $url = $item['url'] ?? $item['detail_url'] ?? $item['link'] ?? '';
        $type = $item['type'] ?? $item['tender_type'] ?? $item['procurement_type'] ?? '';
        $category = $item['category'] ?? $item['tender_category'] ?? '';
        $platform = $item['platform'] ?? $item['source'] ?? '';
        $description = $item['description'] ?? $item['brief'] ?? $item['tender_description'] ?? '';
        $closingLabel = $item['closing_label'] ?? $item['closing_type'] ?? '';

        return [
            'sl_no' => $serial,
            'title' => is_scalar($title) ? (string) $title : '',
            'reference_no' => is_scalar($referenceNo) ? (string) $referenceNo : '',
            'authority' => is_scalar($authority) ? (string) $authority : '',
            'location' => is_scalar($location) ? (string) $location : '',
            'closing_date' => is_scalar($closingDate) ? (string) $closingDate : '',
            'closing_label' => is_scalar($closingLabel) ? (string) $closingLabel : '',
            'emd' => is_scalar($emd) ? (string) $emd : '',
            'tender_value' => is_scalar($value) ? (string) $value : '',
            'type' => is_scalar($type) ? (string) $type : '',
            'category' => is_scalar($category) ? (string) $category : '',
            'platform' => is_scalar($platform) ? (string) $platform : '',
            'description' => is_scalar($description) ? (string) $description : '',
            'url' => is_scalar($url) ? (string) $url : '',
            'raw' => $item,
        ];
    }

    private function filterKeralaOnly($tenders)
    {
        return collect($tenders)->filter(function ($tender) {
            $title = strtolower((string) ($tender['title'] ?? ''));
            $location = strtolower((string) ($tender['location'] ?? ''));
            $description = strtolower((string) ($tender['description'] ?? ''));
            $url = strtolower((string) ($tender['url'] ?? ''));
            if ($title === '' || str_contains($title, 'global tender')) {
                return false;
            }
            if (str_contains($url, '/global-tenders/')) {
                return false;
            }
            $hasKerala = str_contains($title, 'kerala tender')
                || preg_match('/,\s*kerala\b/i', (string) ($tender['location'] ?? ''));
            $hasScrap = str_contains($title, 'scrap')
                || str_contains($description, 'scrap')
                || str_contains(strtolower((string) ($tender['category'] ?? '')), 'scrap');

            return $hasKerala && $hasScrap;
        })->values()->map(function ($tender, $index) {
            $tender['location'] = $this->sanitizeTenderField((string) ($tender['location'] ?? ''));
            $tender['description'] = $this->sanitizeTenderField((string) ($tender['description'] ?? ''));
            $tender['services'] = $tender['type'] ?? '';
            $tender['keyword'] = $tender['platform'] ?? ($tender['type'] ?? '');
            $tender['pricing'] = $tender['tender_value'] ?? '';
            $tender['sl_no'] = $index + 1;
            return $tender;
        });
    }

    private function mergeTenderCandidates($a, $b)
    {
        $merged = collect([]);
        foreach ([$a, $b] as $list) {
            $items = collect($list ?: [])->map(function ($tender) {
                $title = trim((string) ($tender['title'] ?? ''));
                $tender['title'] = $this->sanitizeTenderField($title);
                $tender['location'] = $this->sanitizeTenderField((string) ($tender['location'] ?? ''));
                $tender['description'] = $this->sanitizeTenderField((string) ($tender['description'] ?? ''));
                return $tender;
            })->filter(function ($tender) {
                $title = strtolower(trim((string) ($tender['title'] ?? '')));
                if ($title === '') return false;
                $blocked = [
                    'pricing', 'view all states', 'view all cities', 'view all authorities',
                    'view all keywords', 'view all countries', 'all', 'search',
                ];
                foreach ($blocked as $b) {
                    if ($title === $b) return false;
                }
                return str_contains($title, 'tender');
            });
            $merged = $merged->concat($items);
        }

        return $merged->unique(function ($t) {
            return strtolower(trim((string) ($t['title'] ?? ''))) . '|' .
                strtolower(trim((string) ($t['closing_date'] ?? ''))) . '|' .
                strtolower(trim((string) ($t['location'] ?? '')));
        })->values();
    }

    private function sanitizeTenderField(string $value): string
    {
        $v = trim($value);
        if ($v === '') return '';
        // remove markdown links/images noise
        $v = preg_replace('/!\[[^\]]*\]\([^)]+\)/', '', $v);
        $v = preg_replace('/\[[^\]]+\]\(([^)]+)\)/', '$1', $v);
        $cutTokens = ['View all States', 'View all Cities', 'View all Authorities', 'View all Keywords', 'Pricing', 'All'];
        foreach ($cutTokens as $token) {
            $pos = stripos($v, $token);
            if ($pos !== false && $pos > 0) {
                $v = substr($v, 0, $pos);
                break;
            }
        }
        return trim($v);
    }

    private function fetchViaJina(string $sourceUrl)
    {
        $jinaUrl = 'https://r.jina.ai/http://' . preg_replace('#^https?://#', '', $sourceUrl);
        $proxyResponse = Http::timeout(50)
            ->withHeaders([
                'Accept' => 'text/plain,text/markdown,*/*',
                'User-Agent' => 'Mozilla/5.0 (compatible; ScrapmateAdmin/1.0)',
            ])
            ->get($jinaUrl);

        if (!$proxyResponse->ok()) {
            throw new \RuntimeException('Proxy fetch failed. HTTP status: ' . $proxyResponse->status());
        }

        return (string) $proxyResponse->body();
    }

    private function parseRawTenderText(string $raw)
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw);
        $lines = array_values(array_filter(array_map('trim', $lines), function ($line) {
            return $line !== '';
        }));

        $tenders = [];
        $current = null;
        $sl = 1;
        $skipSet = [
            'Indian Tenders', 'Tender Results', 'Global Tenders', 'Global Tender Results',
            'Saved FiltersKeywordAuthorityCategory (1)State (1)CityTender AmountMore Filters',
            'Saved FiltersKeyword (1)AuthorityCategoryState (1)CityTender AmountMore Filters',
            'Home/Indian Tenders/Active Tenders', 'Reset All', 'Keyword', 'State', 'Active(42)',
            'Active(243)',
            'Archived', 'Followed', 'search', 'Did not find the tender you are looking for?'
        ];

        $pushCurrent = function () use (&$current, &$tenders, &$sl) {
            if (!$current) {
                return;
            }
            if (!empty($current['title']) || !empty($current['description'])) {
                $current['sl_no'] = $sl++;
                $tenders[] = $current;
            }
            $current = null;
        };

        foreach ($lines as $line) {
            if (in_array($line, $skipSet, true)) {
                continue;
            }
            if (str_starts_with($line, 'View all ') || $line === 'All') {
                // Skip footer/filter lines but keep parsing remaining lines.
                continue;
            }

            if (preg_match('/Tender\s*-\s*Kerala Tender$/i', $line)) {
                $pushCurrent();
                $current = [
                    'title' => $line,
                    'reference_no' => '',
                    'authority' => trim(preg_replace('/\s*Tender\s*-\s*Kerala Tender$/i', '', $line)),
                    'location' => '',
                    'closing_date' => '',
                    'closing_label' => '',
                    'emd' => '',
                    'tender_value' => '',
                    'type' => '',
                    'category' => '',
                    'platform' => '',
                    'description' => '',
                    'url' => '',
                    'raw' => [],
                ];
                continue;
            }

            if (!$current) {
                continue;
            }

            if (strtolower($line) === 'location') {
                continue;
            }
            if (str_starts_with($line, 'Description:')) {
                $current['description'] = $this->sanitizeTenderField(trim(substr($line, strlen('Description:'))));
                continue;
            }
            if (strtolower($line) === 'closing date' || strtolower($line) === 'closing soon') {
                $current['closing_label'] = $line;
                continue;
            }
            if (strtolower($line) === 'tender amount') {
                continue;
            }

            if ($current['type'] === '' && in_array($line, ['Auction', 'Goods', 'Works', 'Services'], true)) {
                $current['type'] = $line;
                continue;
            }
            if ($current['category'] === '' && !in_array($line, ['MSTC', 'Eprocure'], true) && !str_contains($line, ',')) {
                // likely category line after type
                $current['category'] = $line;
                continue;
            }
            if ($current['platform'] === '' && in_array($line, ['MSTC', 'Eprocure'], true)) {
                $current['platform'] = $line;
                continue;
            }
            if ($current['location'] === '' && str_contains($line, 'Kerala')) {
                $current['location'] = $line;
                continue;
            }
            if ($current['closing_date'] === '' && preg_match('/\b\d{1,2}\s+[A-Za-z]{3}\s+\d{4}\b/', $line)) {
                $current['closing_date'] = $line;
                continue;
            }
            if ($current['tender_value'] === '' && ($line === 'Refer Documents' || str_starts_with($line, '₹'))) {
                $current['tender_value'] = $line;
                continue;
            }
        }

        $pushCurrent();
        return collect($tenders);
    }

    private function parseJinaMarkdownTenders(string $markdown)
    {
        $lines = preg_split('/\r\n|\r|\n/', $markdown);
        $lines = array_values(array_map('trim', $lines ?: []));
        $tenders = [];
        $current = null;
        $sl = 1;

        $pushCurrent = function () use (&$current, &$tenders, &$sl) {
            if (!$current) return;
            if (!empty($current['title'])) {
                $current['sl_no'] = $sl++;
                $tenders[] = $current;
            }
            $current = null;
        };

        foreach ($lines as $line) {
            if ($line === '' || str_starts_with($line, '![Image')) {
                continue;
            }
            if (str_starts_with($line, 'View all ') || $line === 'All') {
                continue;
            }

            if (preg_match('/^\[(.+?)\]\((https?:\/\/[^\s)]+)(?:\s+"([^"]*)")?\)$/', $line, $m)) {
                $title = trim($m[1]);
                $url = trim($m[2]);
                $hint = isset($m[3]) ? trim($m[3]) : '';
                // Tender card title lines generally have detail links
                if (
                    str_contains($url, '/detail-') &&
                    !str_contains(strtolower($url), '/global-tenders/') &&
                    str_contains($title, 'Tender') &&
                    str_contains(strtolower($title), 'kerala')
                ) {
                    $pushCurrent();
                    $current = [
                        'sl_no' => null,
                        'title' => $title,
                        'reference_no' => '',
                        'authority' => trim(preg_replace('/\s*-\s*.*Tender$/i', '', $title)),
                        'location' => '',
                        'closing_date' => '',
                        'closing_label' => '',
                        'emd' => '',
                        'tender_value' => '',
                        'type' => '',
                        'category' => '',
                        'platform' => '',
                        'description' => '',
                        'url' => $url,
                        'raw' => ['hint' => $hint],
                    ];
                    if ($hint !== '') {
                        $current['reference_no'] = $hint;
                    }
                    continue;
                }
            }

            if (!$current) {
                continue;
            }

            if (in_array($line, ['Auction', 'Goods', 'Works', 'Services'], true) && $current['type'] === '') {
                $current['type'] = $line;
                continue;
            }
            if (in_array($line, ['Scraps', 'Aerospace and Defence', 'Metals', 'Electronics'], true) && $current['category'] === '') {
                $current['category'] = $line;
                continue;
            }
            if (in_array($line, ['MSTC', 'Eprocure', 'GeM', 'CPPP'], true) && $current['platform'] === '') {
                $current['platform'] = $line;
                continue;
            }
            if (preg_match('/\blocation\b/i', $line) || str_starts_with($line, '[![Image')) {
                continue;
            }
            if (str_starts_with($line, 'Description:')) {
                $current['description'] = trim(substr($line, strlen('Description:')));
                continue;
            }
            if (preg_match('/^(Closing Soon|Closing Date)\s+(.+)$/i', $line, $m)) {
                $current['closing_label'] = $m[1];
                $current['closing_date'] = trim($m[2]);
                continue;
            }
            if (preg_match('/^Tender Amount\s+(.+)$/i', $line, $m)) {
                $current['tender_value'] = trim($m[1]);
                continue;
            }
            if ($current['location'] === '' && (str_contains($line, 'Kerala') || $line === 'India' || str_contains($line, ', '))) {
                $current['location'] = $this->sanitizeTenderField(trim(preg_replace('/^\[.*?\]\(.*?\)\s*/', '', $line)));
                continue;
            }
        }

        $pushCurrent();
        return collect($tenders);
    }

    private function parseTenderDetailMarkdown(string $markdown, array $baseTender)
    {
        $detail = $baseTender;
        $detail['documents'] = [];

        if (preg_match('/Opening Date\s+([0-9]{1,2}\s+[A-Za-z]{3}\s+[0-9]{4})/i', $markdown, $m)) {
            $detail['opening_date'] = trim($m[1]);
        }
        if (preg_match('/(Closing Soon|Closing Date)\s+([0-9]{1,2}\s+[A-Za-z]{3}\s+[0-9]{4})/i', $markdown, $m)) {
            $detail['closing_label'] = trim($m[1]);
            $detail['closing_date'] = trim($m[2]);
        }
        if (preg_match('/Tender Amount\s+([^\n\r]+)/i', $markdown, $m)) {
            $detail['tender_amount'] = trim($m[1]);
        }
        if (preg_match('/###\s*EMD[\s\S]*?\n([^\n\r]+)/i', $markdown, $m)) {
            $emd = trim($m[1]);
            if ($emd !== '') {
                $detail['emd'] = $emd;
            }
        }
        if (preg_match('/###\s*Tender Id\s*\n+([^\n\r]+)/i', $markdown, $m)) {
            $detail['tender_id'] = trim($m[1]);
        }
        if (preg_match('/###\s*Tender No\s*\n+([^\n\r]+)/i', $markdown, $m)) {
            $detail['tender_no'] = trim($m[1]);
        }
        if (preg_match('/###\s*Tender Authority[\s\S]*?\n+([^\n\r]+)/i', $markdown, $m)) {
            $detail['tender_authority'] = trim(strip_tags($m[1]));
        }
        if (preg_match('/(?:\+91[\s-]?)?[6-9]\d{9}/', $markdown, $m)) {
            $detail['phone_number'] = preg_replace('/[\s-]+/', '', trim($m[0]));
        }
        if (preg_match('/###\s*Purchaser Address\s*\n+([^\n\r]+)/i', $markdown, $m)) {
            $detail['purchaser_address'] = trim($m[1]);
        }
        if (preg_match('/###\s*Website[\s\S]*?\((https?:\/\/[^\s)]+)\)/i', $markdown, $m)) {
            $detail['website'] = trim($m[1]);
        }
        if (preg_match('/###\s*Tender URL[\s\S]*?\((https?:\/\/[^\s)]+)\)/i', $markdown, $m)) {
            $detail['url'] = trim($m[1]);
        } elseif (preg_match('/Tender URL\s*\n+\[.*?\]\((https?:\/\/[^\s)]+)\)/i', $markdown, $m)) {
            $detail['url'] = trim($m[1]);
        }
        if (preg_match('/###\s*Description\s*[\s\S]*?\n([^\n\r]+)/i', $markdown, $m)) {
            $d = trim($m[1]);
            if ($d !== '' && !str_contains(strtolower($d), 'unlock the tender details')) {
                $detail['description'] = $d;
            }
        }

        // Documents block extraction
        if (preg_match('/Documents([\s\S]*?)Report Missing Document/i', $markdown, $m)) {
            $docBlock = $m[1];
            if (preg_match_all('/\[(.*?)\]\((https?:\/\/[^\s)]+)(?:\s+"[^"]*")?\)/', $docBlock, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $labelRaw = trim($match[1]);
                    $url = trim($match[2]);
                    if ($labelRaw === '' || str_contains(strtolower($labelRaw), 'download all')) {
                        continue;
                    }

                    $fileName = $labelRaw;
                    $fileSize = '';
                    if (preg_match('/\b([0-9]+(?:\.[0-9]+)?\s*(?:kB|MB|GB))\b/i', $labelRaw, $sz)) {
                        $fileSize = $sz[1];
                    }
                    if (preg_match('/([A-Za-z0-9_\- .]+\.(?:pdf|docx?|xlsx?|zip|html))/i', $labelRaw, $fn)) {
                        $fileName = $fn[1];
                    }

                    $detail['documents'][] = [
                        'doc_label' => $labelRaw,
                        'file_name' => $fileName,
                        'file_size' => $fileSize,
                        'doc_url' => $url,
                    ];
                }
            }
        }

        return $detail;
    }

    private function persistTenderV2ScrapedTenders($tenders, string $sourceListUrl)
    {
        $savedTenders = 0;
        $savedDocs = 0;
        $errorMsg = null;
        $skipped = 0;
        $enrichedTenders = [];

        try {
            $tenderCollection = collect($tenders)->values();
            foreach ($tenderCollection as $tender) {
                $detail = (array) $tender;
                $raw = is_array($detail['raw'] ?? null) ? $detail['raw'] : [];
                $downloadedDocuments = collect($raw['downloadedDocuments'] ?? [])->filter(function ($doc) {
                    return strtolower(trim((string) ($doc['status'] ?? ''))) === 'downloaded'
                        && trim((string) ($doc['savedTo'] ?? '')) !== '';
                })->values();

                if ($downloadedDocuments->isNotEmpty()) {
                    $detail['documents'] = $downloadedDocuments->map(function ($doc) {
                        $name = trim((string) (($doc['name'] ?? '') ?: ($doc['title'] ?? 'Document')));
                        return [
                            'doc_label' => (string) (($doc['title'] ?? '') ?: $name),
                            'file_name' => $name !== '' ? $name : 'Document',
                            'file_size' => '',
                            // Local path from scraper output; uploader converts to S3 URL.
                            'doc_url' => (string) $doc['savedTo'],
                            's3_key' => '',
                            's3_url' => '',
                        ];
                    })->toArray();
                } else {
                    $skipped++;
                }

                $detailHtmlPath = $downloadedDocuments->map(function ($doc) {
                    return trim((string) ($doc['savedTo'] ?? ''));
                })->first(function ($p) {
                    return $p !== '' && is_file($p) && preg_match('/\.html?$/i', $p);
                });
                if (is_string($detailHtmlPath) && $detailHtmlPath !== '') {
                    $addr = $this->extractPurchaserAddressFromDetailHtml($detailHtmlPath);
                    if ($addr !== '') {
                        $detail['raw']['purchaser_address'] = $addr;
                    }
                }

                $website = trim((string) ($detail['raw']['website'] ?? $detail['website'] ?? ''));
                if ($website !== '' && !str_starts_with(strtolower($website), 'http://') && !str_starts_with(strtolower($website), 'https://')) {
                    $website = 'https://' . ltrim($website, '/');
                }
                $website = rtrim($website, '/');
                if ($website !== '') {
                    $detail['raw']['website'] = $website;
                }
                $tenderUrl = trim((string) ($detail['raw']['tender_url'] ?? $detail['url'] ?? ''));
                if ($tenderUrl === '' && str_contains(strtolower($website), 'tntenders.gov.in')) {
                    $tenderUrl = 'https://tntenders.gov.in/nicgep/app?component=%24DirectLink&page=FrontEndViewTender&service=direct&session=T';
                }
                if ($tenderUrl !== '') {
                    $detail['raw']['tender_url'] = $tenderUrl;
                    $detail['url'] = $tenderUrl;
                }

                $trace = [];
                $detail = $this->uploadTenderDocumentsToS3($detail, $trace);
                $detail['documents_count'] = count($detail['documents'] ?? []);
                $detail['raw_payload'] = json_encode([
                    'source' => 'tender_v2_scrape_tender_codejs',
                    'source_list_url' => $sourceListUrl,
                    'scraped_at' => now()->toIso8601String(),
                    'upload_trace' => $trace,
                    'raw' => $raw,
                    'source_tender' => $tender,
                ]);
                $enrichedTenders[] = $detail;
            }

            if (empty($enrichedTenders)) {
                return [
                    'saved_tenders' => 0,
                    'saved_docs' => 0,
                    'skipped_tenders' => $skipped,
                    'error' => null,
                    'tenders' => [],
                ];
            }

            $syncPayload = [
                'source_list_url' => $sourceListUrl,
                'tenders' => $enrichedTenders,
            ];
            $syncResponse = $this->nodeApi->post('/accounts/tenders-sync', $syncPayload, 300);
            if (($syncResponse['status'] ?? 'error') === 'success') {
                $savedTenders = (int) (($syncResponse['data']['saved_tenders'] ?? 0));
                $savedDocs = (int) (($syncResponse['data']['saved_docs'] ?? 0));
                $skipped += (int) (($syncResponse['data']['skipped_tenders'] ?? 0));
                if (!empty($syncResponse['data']['errors']) && is_array($syncResponse['data']['errors'])) {
                    $errorMsg = 'Partial sync errors: ' . count($syncResponse['data']['errors']);
                }
            } else {
                $errorMsg = (string) ($syncResponse['msg'] ?? 'Failed to sync Tender V2 tenders');
            }
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            Log::error('Tender V2 persistence failed', ['error' => $errorMsg]);
        }

        return [
            'saved_tenders' => $savedTenders,
            'saved_docs' => $savedDocs,
            'skipped_tenders' => $skipped,
            'error' => $errorMsg,
            'tenders' => $enrichedTenders,
        ];
    }

    private function extractPurchaserAddressFromDetailHtml(string $filePath): string
    {
        try {
            $html = (string) file_get_contents($filePath);
            if ($html === '') return '';
            $pattern = '/Purchaser\s*Address[\s\S]{0,200}?<td[^>]*class="td_field"[^>]*>\s*([^<]+?)\s*<\/td>/i';
            if (preg_match($pattern, $html, $m)) {
                return trim(html_entity_decode(strip_tags((string) $m[1])));
            }
            $altPattern = '/Tender\s*Inviting\s*Authority[\s\S]{0,1200}?<td[^>]*class="td_caption"[^>]*>\s*<b>\s*Address\s*<\/b>\s*<\/td>\s*<td[^>]*class="td_field"[^>]*>\s*([^<]+?)\s*<\/td>/i';
            if (preg_match($altPattern, $html, $m)) {
                return trim(html_entity_decode(strip_tags((string) $m[1])));
            }
        } catch (\Throwable $e) {
            return '';
        }
        return '';
    }

    private function resolveScrapeTenderDir(): string
    {
        $repoRoot = dirname(base_path());
        $candidates = [
            $repoRoot . '/scrapetender',
            $repoRoot . '/scrape tender',
        ];
        foreach ($candidates as $dir) {
            if (is_dir($dir) && is_file($dir . '/code.js')) {
                return $dir;
            }
        }
        throw new \RuntimeException('scrapetender directory not found');
    }

    private function resolveScrapeTenderOutputRoots(): array
    {
        $repoRoot = dirname(base_path());
        $roots = [
            realpath($repoRoot . '/scrapetender/output') ?: '',
            realpath($repoRoot . '/scrape tender/output') ?: '',
        ];
        return array_values(array_filter($roots, function ($p) {
            return is_string($p) && trim($p) !== '';
        }));
    }

    private function persistTenderDetails($tenders, string $sourceListUrl)
    {
        $savedTenders = 0;
        $savedDocs = 0;
        $errorMsg = null;
        $enrichedTenders = [];
        $skippedAlreadySaved = 0;

        try {
            $tenderCollection = collect($tenders)->values();
            $existingHashes = [];
            $checkPayload = [
                'tenders' => $tenderCollection->map(function ($tender) {
                    return [
                        'url' => $tender['url'] ?? '',
                        'title' => $tender['title'] ?? '',
                        'authority' => $tender['authority'] ?? '',
                        'closing_date' => $tender['closing_date'] ?? '',
                    ];
                })->toArray()
            ];
            $checkResponse = $this->nodeApi->post('/accounts/tenders-existing-check', $checkPayload, 120);
            if (($checkResponse['status'] ?? 'error') === 'success' && !empty($checkResponse['data']['existing_hashes'])) {
                $existingHashes = $checkResponse['data']['existing_hashes'];
            }

            foreach ($tenderCollection as $tender) {
                $hash = $this->buildTenderHash($tender);
                if (in_array($hash, $existingHashes, true)) {
                    $skippedAlreadySaved++;
                    continue;
                }

                $detail = $tender;
                $detailMarkdown = '';
                if (!empty($tender['url'])) {
                    $detailUrl = (string) $tender['url'];
                    $lowerUrl = strtolower($detailUrl);
                    // Skip non-kerala/global detail pages; they inject unrelated footer/filter content.
                    if (!str_contains($lowerUrl, '/global-tenders/') && str_contains($lowerUrl, 'kerala')) {
                        $detailMarkdown = $this->fetchViaJina($detailUrl);
                        $detail = $this->parseTenderDetailMarkdown($detailMarkdown, $tender);
                    }
                }
                $detail['raw_payload'] = $detailMarkdown !== '' ? $detailMarkdown : json_encode($detail);
                $detail['documents_count'] = count($detail['documents'] ?? []);
                $enrichedTenders[] = $detail;
            }

            // Persist to DynamoDB + S3 via Node backend API.
            $syncPayload = [
                'source_list_url' => $sourceListUrl,
                'tenders' => $enrichedTenders,
            ];
            $syncResponse = $this->nodeApi->post('/accounts/tenders-sync', $syncPayload, 300);
            if (($syncResponse['status'] ?? 'error') === 'success') {
                $savedTenders = (int) (($syncResponse['data']['saved_tenders'] ?? 0));
                $savedDocs = (int) (($syncResponse['data']['saved_docs'] ?? 0));
                $skippedAlreadySaved += (int) (($syncResponse['data']['skipped_tenders'] ?? 0));
                if (!empty($syncResponse['data']['errors']) && is_array($syncResponse['data']['errors'])) {
                    $errorMsg = 'Partial sync errors: ' . count($syncResponse['data']['errors']);
                }
            } else {
                $errorMsg = $syncResponse['msg'] ?? 'Failed to sync tenders to AWS';
            }
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            Log::error('Tender persistence failed', ['error' => $errorMsg]);
        }

        return [
            'saved_tenders' => $savedTenders,
            'saved_docs' => $savedDocs,
            'skipped_tenders' => $skippedAlreadySaved,
            'error' => $errorMsg,
            'tenders' => $enrichedTenders,
        ];
    }

    private function buildTenderHash(array $tender)
    {
        $sourceUrl = trim((string) ($tender['url'] ?? $tender['source_url'] ?? ''));
        $title = trim((string) ($tender['title'] ?? ''));
        $authority = trim((string) ($tender['authority'] ?? $tender['tender_authority'] ?? ''));
        $closingDate = trim((string) ($tender['closing_date'] ?? ''));
        return md5($sourceUrl . '|' . $title . '|' . $authority . '|' . $closingDate);
    }

    private function runPuppeteerScrape(string $sourceUrl): array
    {
        try {
            $scriptPath = base_path('scripts/scrape-tenders-puppeteer.cjs');
            if (!file_exists($scriptPath)) {
                return ['ok' => false, 'error' => 'Puppeteer script not found'];
            }

            $process = new Process(['node', $scriptPath, $sourceUrl]);
            $process->setTimeout(90);
            $process->run();

            if (!$process->isSuccessful()) {
                return [
                    'ok' => false,
                    'error' => trim($process->getErrorOutput() ?: $process->getOutput() ?: 'Puppeteer process failed')
                ];
            }

            $out = trim($process->getOutput());
            $json = json_decode($out, true);
            if (!is_array($json)) {
                return ['ok' => false, 'error' => 'Invalid Puppeteer JSON output'];
            }

            return [
                'ok' => ($json['status'] ?? '') === 'success',
                'items' => $json['data'] ?? [],
                'error' => $json['msg'] ?? null
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function runBrowsershotScrape(string $sourceUrl): array
    {
        try {
            $jinaFallback = function (string $url): array {
                try {
                    $proxyBody = $this->fetchViaJina($url);
                    $proxyParsed = $this->mergeTenderCandidates(
                        $this->parseJinaMarkdownTenders($proxyBody),
                        $this->parseRawTenderText($proxyBody)
                    );
                    $filtered = $this->filterKeralaOnly($proxyParsed)->take(10)->values()->toArray();
                    if (!empty($filtered)) {
                        return ['ok' => true, 'items' => $filtered, 'error' => null];
                    }
                    return ['ok' => false, 'items' => [], 'error' => 'jina fallback returned no Kerala scrap tenders'];
                } catch (\Throwable $e) {
                    return ['ok' => false, 'items' => [], 'error' => 'jina fallback failed: ' . $e->getMessage()];
                }
            };

            if (!class_exists(\Spatie\Browsershot\Browsershot::class)) {
                return $jinaFallback($sourceUrl);
            }

            $browser = \Spatie\Browsershot\Browsershot::url($sourceUrl)
                ->setDelay(3500)
                ->timeout(90)
                ->noSandbox();

            $html = $browser->bodyHtml();

            if (!is_string($html) || trim($html) === '') {
                return ['ok' => false, 'error' => 'Browsershot returned empty HTML'];
            }

            $items = [];
            if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $html, $m)) {
                $nextData = json_decode($m[1], true);
                if (is_array($nextData)) {
                    $items = $this->findTenderItemsInTree($nextData);
                }
            }

            if (!is_array($items) || empty($items)) {
                // DOM fallback for pages that don't expose tender list via __NEXT_DATA__
                $domItemsRaw = $browser->evaluate(<<<'JS'
(() => {
  const links = Array.from(document.querySelectorAll('a[href*="/detail-"]'));
  const out = [];

  const pickLine = (lines, predicate) => {
    for (const l of lines) {
      if (predicate(l)) return l;
    }
    return '';
  };

  for (const a of links) {
    const href = a.getAttribute('href') || '';
    if (!href || href.includes('/global-tenders/')) continue;
    const title = (a.textContent || '').trim();
    if (!title || !/tender/i.test(title)) continue;

    const card = a.closest('article,section,div') || a.parentElement;
    const text = (card?.innerText || '').replace(/\r/g, '\n');
    const lines = text.split('\n').map(s => s.trim()).filter(Boolean);

    const type = pickLine(lines, l => ['Auction','Goods','Works','Services'].includes(l));
    const category = pickLine(lines, l => /scrap/i.test(l));
    const platform = pickLine(lines, l => ['MSTC','Eprocure','GeM','CPPP','Nprocure','Ireps'].includes(l));
    const location = pickLine(lines, l => /,\s*Kerala$/i.test(l) || /\bKerala\b/i.test(l));
    const closingLine = pickLine(lines, l => /^Closing (Soon|Date)/i.test(l));
    const amountIdx = lines.findIndex(l => /^Tender Amount$/i.test(l));
    const amount = amountIdx >= 0 && lines[amountIdx + 1] ? lines[amountIdx + 1] : '';
    const descLine = pickLine(lines, l => /^Description:/i.test(l));
    const desc = descLine.replace(/^Description:\s*/i, '');

    out.push({
      title,
      url: href.startsWith('http') ? href : `https://bidassist.com${href}`,
      authority: title.replace(/\s*-\s*.*Tender$/i, '').trim(),
      type,
      category: category || 'Scraps',
      platform,
      location,
      closing_label: closingLine ? closingLine.split(/\s+/).slice(0,2).join(' ') : '',
      closing_date: closingLine ? closingLine.replace(/^Closing (Soon|Date)\s*/i, '').trim() : '',
      tender_value: amount || 'Refer Documents',
      description: desc || ''
    });
  }

  const seen = new Set();
  const deduped = out.filter(i => {
    const key = `${i.title}|${i.closing_date}|${i.location}|${i.url}`;
    if (seen.has(key)) return false;
    seen.add(key);
    return true;
  });
  return JSON.stringify(deduped);
})()
JS
                );

                $domItems = [];
                if (is_string($domItemsRaw) && trim($domItemsRaw) !== '') {
                    $decodedDom = json_decode($domItemsRaw, true);
                    if (is_array($decodedDom)) {
                        $domItems = $decodedDom;
                    }
                }

                if (is_array($domItems) && !empty($domItems)) {
                    $items = $domItems;
                } else {
                    // Plain text fallback from rendered page body
                    $bodyTextRaw = $browser->evaluate('document.body && document.body.innerText ? document.body.innerText : ""');
                    $bodyText = is_string($bodyTextRaw) ? trim($bodyTextRaw) : '';
                    if ($bodyText !== '') {
                        $textParsed = $this->parseRawTenderText($bodyText);
                        if ($textParsed->isNotEmpty()) {
                            $items = $textParsed->values()->toArray();
                        } else {
                            // Final in-function fallback: proxy text fetch via jina and parse locally
                            $proxyBody = $this->fetchViaJina($sourceUrl);
                            $proxyParsed = $this->mergeTenderCandidates(
                                $this->parseJinaMarkdownTenders($proxyBody),
                                $this->parseRawTenderText($proxyBody)
                            );
                            if ($proxyParsed->isNotEmpty()) {
                                $items = $proxyParsed->values()->toArray();
                            } else {
                                $jina = $jinaFallback($sourceUrl);
                                if (!empty($jina['ok'])) {
                                    return $jina;
                                }
                                return ['ok' => false, 'error' => $jina['error'] ?? 'No tender list in __NEXT_DATA__, DOM, rendered text, or jina proxy'];
                            }
                        }
                    } else {
                        // Final in-function fallback: proxy text fetch via jina and parse locally
                        $proxyBody = $this->fetchViaJina($sourceUrl);
                        $proxyParsed = $this->mergeTenderCandidates(
                            $this->parseJinaMarkdownTenders($proxyBody),
                            $this->parseRawTenderText($proxyBody)
                        );
                        if ($proxyParsed->isNotEmpty()) {
                            $items = $proxyParsed->values()->toArray();
                        } else {
                            $jina = $jinaFallback($sourceUrl);
                            if (!empty($jina['ok'])) {
                                return $jina;
                            }
                            return ['ok' => false, 'error' => $jina['error'] ?? 'No tender list in __NEXT_DATA__, DOM, rendered text, or jina proxy'];
                        }
                    }
                }
            }

            $normalized = collect($items)->map(function ($item, $idx) {
                $arr = (array) $item;
                if (!empty($arr['title']) && array_key_exists('tender_value', $arr)) {
                    return [
                        'sl_no' => $idx + 1,
                        'title' => (string) ($arr['title'] ?? ''),
                        'reference_no' => (string) ($arr['reference_no'] ?? ''),
                        'authority' => (string) ($arr['authority'] ?? ''),
                        'location' => (string) ($arr['location'] ?? ''),
                        'closing_date' => (string) ($arr['closing_date'] ?? ''),
                        'closing_label' => (string) ($arr['closing_label'] ?? ''),
                        'emd' => (string) ($arr['emd'] ?? ''),
                        'tender_value' => (string) ($arr['tender_value'] ?? ''),
                        'type' => (string) ($arr['type'] ?? ''),
                        'category' => (string) ($arr['category'] ?? ''),
                        'platform' => (string) ($arr['platform'] ?? ''),
                        'description' => (string) ($arr['description'] ?? ''),
                        'url' => (string) ($arr['url'] ?? ''),
                        'phone_number' => (string) ($arr['phone_number'] ?? ''),
                    ];
                }
                return $this->normalizeTender($arr, $idx + 1);
            })->values();

            return [
                'ok' => true,
                'items' => $this->filterKeralaOnly($normalized)->take(10)->values()->toArray(),
                'error' => null,
            ];
        } catch (\Throwable $e) {
            try {
                $proxyBody = $this->fetchViaJina($sourceUrl);
                $proxyParsed = $this->mergeTenderCandidates(
                    $this->parseJinaMarkdownTenders($proxyBody),
                    $this->parseRawTenderText($proxyBody)
                );
                $filtered = $this->filterKeralaOnly($proxyParsed)->take(10)->values()->toArray();
                if (!empty($filtered)) {
                    return ['ok' => true, 'items' => $filtered, 'error' => null];
                }
            } catch (\Throwable $inner) {
                return ['ok' => false, 'error' => $e->getMessage() . ' | fallback failed: ' . $inner->getMessage()];
            }
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
