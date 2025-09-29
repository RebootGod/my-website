<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * ========================================
 * DATA EXFILTRATION DETECTION SERVICE
 * Advanced monitoring for data exfiltration patterns and mass data access
 * Following workinginstruction.md: Separate file for data exfiltration detection
 * ========================================
 */
class DataExfiltrationDetectionService
{
    // Detection thresholds
    private const MASS_ACCESS_THRESHOLD = 1000; // Records accessed in timeframe
    private const RAPID_ACCESS_THRESHOLD = 100; // Records per minute
    private const DOWNLOAD_SIZE_THRESHOLD = 50 * 1024 * 1024; // 50MB
    private const EXPORT_FREQUENCY_THRESHOLD = 10; // Exports per hour
    private const SEARCH_PATTERN_THRESHOLD = 50; // Searches per hour
    private const TIME_WINDOW_MINUTES = 60; // Analysis time window
    
    /**
     * Analyze request for data exfiltration patterns
     * 
     * @param User $user
     * @param Request $request
     * @param array $responseContext
     * @return array Exfiltration analysis result
     */
    public function analyzeDataExfiltration(User $user, Request $request, array $responseContext = []): array
    {
        $analysis = [
            'user_id' => $user->id,
            'analysis_timestamp' => now()->toISOString(),
            'exfiltration_indicators' => [],
            'risk_score' => 0,
            'immediate_action_required' => false,
        ];
        
        // Check mass data access pattern
        $massAccessResult = $this->detectMassDataAccess($user);
        if ($massAccessResult['detected']) {
            $analysis['exfiltration_indicators'][] = $massAccessResult;
            $analysis['risk_score'] += 40;
        }
        
        // Check rapid sequential access
        $rapidAccessResult = $this->detectRapidSequentialAccess($user);
        if ($rapidAccessResult['detected']) {
            $analysis['exfiltration_indicators'][] = $rapidAccessResult;
            $analysis['risk_score'] += 35;
        }
        
        // Check download/export patterns
        $downloadResult = $this->detectSuspiciousDownloads($user, $request, $responseContext);
        if ($downloadResult['suspicious']) {
            $analysis['exfiltration_indicators'][] = $downloadResult;
            $analysis['risk_score'] += 30;
        }
        
        // Check search enumeration patterns
        $searchResult = $this->detectSearchEnumeration($user);
        if ($searchResult['detected']) {
            $analysis['exfiltration_indicators'][] = $searchResult;
            $analysis['risk_score'] += 25;
        }
        
        // Check data scraping patterns
        $scrapingResult = $this->detectDataScraping($user, $request);
        if ($scrapingResult['detected']) {
            $analysis['exfiltration_indicators'][] = $scrapingResult;
            $analysis['risk_score'] += 45;
        }
        
        // Check API abuse for data extraction
        $apiAbuseResult = $this->detectAPIDataAbuse($user, $request);
        if ($apiAbuseResult['detected']) {
            $analysis['exfiltration_indicators'][] = $apiAbuseResult;
            $analysis['risk_score'] += 35;
        }
        
        // Check for database enumeration
        $dbEnumResult = $this->detectDatabaseEnumeration($user, $request);
        if ($dbEnumResult['detected']) {
            $analysis['exfiltration_indicators'][] = $dbEnumResult;
            $analysis['risk_score'] += 50;
        }
        
        $analysis['immediate_action_required'] = $analysis['risk_score'] >= 70;
        $analysis['risk_level'] = $this->getRiskLevel($analysis['risk_score']);
        $analysis['recommendations'] = $this->generateExfiltrationRecommendations($analysis);
        
        return $analysis;
    }
    
    /**
     * Detect mass data access patterns
     * 
     * @param User $user
     * @return array Mass access detection result
     */
    public function detectMassDataAccess(User $user): array
    {
        $cacheKey = "data_access_volume:{$user->id}";
        $accessLog = Cache::get($cacheKey, []);
        
        $timeWindow = now()->subMinutes(self::TIME_WINDOW_MINUTES)->timestamp;
        $recentAccess = collect($accessLog)->where('timestamp', '>', $timeWindow);
        
        $totalRecords = $recentAccess->sum('record_count');
        $uniqueResources = $recentAccess->pluck('resource_type')->unique()->count();
        
        $detected = $totalRecords > self::MASS_ACCESS_THRESHOLD;
        
        if ($detected) {
            Log::channel('security')->critical('Mass Data Access Detected', [
                'user_id' => $user->id,
                'records_accessed' => $totalRecords,
                'unique_resources' => $uniqueResources,
                'time_window_minutes' => self::TIME_WINDOW_MINUTES,
            ]);
        }
        
        return [
            'indicator_type' => 'mass_data_access',
            'detected' => $detected,
            'records_accessed' => $totalRecords,
            'unique_resources' => $uniqueResources,
            'threshold' => self::MASS_ACCESS_THRESHOLD,
            'severity' => $detected ? 'critical' : 'low',
            'details' => [
                'access_patterns' => $recentAccess->groupBy('resource_type')->map->count()->toArray(),
                'time_distribution' => $this->analyzeTimeDistribution($recentAccess),
            ],
        ];
    }
    
    /**
     * Track data access for volume monitoring
     * 
     * @param int $userId
     * @param string $resourceType
     * @param int $recordCount
     * @param array $metadata
     * @return void
     */
    public function trackDataAccess(int $userId, string $resourceType, int $recordCount = 1, array $metadata = []): void
    {
        $cacheKey = "data_access_volume:{$userId}";
        $accessLog = Cache::get($cacheKey, []);
        
        $accessLog[] = [
            'resource_type' => $resourceType,
            'record_count' => $recordCount,
            'timestamp' => now()->timestamp,
            'metadata' => $metadata,
        ];
        
        // Keep last 200 access records
        $accessLog = array_slice($accessLog, -200);
        
        // Cache for 4 hours
        Cache::put($cacheKey, $accessLog, 14400);
    }
    
    /**
     * Detect rapid sequential data access
     * 
     * @param User $user
     * @return array Rapid access detection result
     */
    public function detectRapidSequentialAccess(User $user): array
    {
        $cacheKey = "rapid_access:{$user->id}";
        $accessHistory = Cache::get($cacheKey, []);
        
        $lastMinute = now()->subMinute()->timestamp;
        $recentAccess = collect($accessHistory)->where('timestamp', '>', $lastMinute);
        
        $recordsPerMinute = $recentAccess->sum('record_count');
        $requestsPerMinute = $recentAccess->count();
        
        $detected = $recordsPerMinute > self::RAPID_ACCESS_THRESHOLD || $requestsPerMinute > 30;
        
        return [
            'indicator_type' => 'rapid_sequential_access',
            'detected' => $detected,
            'records_per_minute' => $recordsPerMinute,
            'requests_per_minute' => $requestsPerMinute,
            'threshold_records' => self::RAPID_ACCESS_THRESHOLD,
            'threshold_requests' => 30,
            'severity' => $detected ? 'high' : 'low',
        ];
    }
    
    /**
     * Track rapid access patterns
     * 
     * @param int $userId
     * @param int $recordCount
     * @return void
     */
    public function trackRapidAccess(int $userId, int $recordCount = 1): void
    {
        $cacheKey = "rapid_access:{$userId}";
        $history = Cache::get($cacheKey, []);
        
        $history[] = [
            'record_count' => $recordCount,
            'timestamp' => now()->timestamp,
        ];
        
        // Keep last 100 access points
        $history = array_slice($history, -100);
        
        // Cache for 2 hours
        Cache::put($cacheKey, $history, 7200);
    }
    
    /**
     * Detect suspicious download patterns
     * 
     * @param User $user
     * @param Request $request
     * @param array $responseContext
     * @return array Suspicious download detection
     */
    public function detectSuspiciousDownloads(User $user, Request $request, array $responseContext): array
    {
        $cacheKey = "download_activity:{$user->id}";
        $downloadHistory = Cache::get($cacheKey, []);
        
        $currentHour = now()->subHour()->timestamp;
        $recentDownloads = collect($downloadHistory)->where('timestamp', '>', $currentHour);
        
        $downloadCount = $recentDownloads->count();
        $totalSize = $recentDownloads->sum('size_bytes');
        
        // Check current request for download/export indicators
        $isDownloadRequest = $this->isDownloadRequest($request, $responseContext);
        $largeResponse = ($responseContext['size_bytes'] ?? 0) > self::DOWNLOAD_SIZE_THRESHOLD;
        
        $suspicious = $downloadCount > self::EXPORT_FREQUENCY_THRESHOLD || 
                     $totalSize > (100 * 1024 * 1024) || // 100MB per hour
                     ($isDownloadRequest && $largeResponse);
        
        return [
            'indicator_type' => 'suspicious_downloads',
            'suspicious' => $suspicious,
            'download_count_hour' => $downloadCount,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'current_request_download' => $isDownloadRequest,
            'current_response_large' => $largeResponse,
            'severity' => $suspicious ? 'high' : 'low',
            'thresholds' => [
                'frequency' => self::EXPORT_FREQUENCY_THRESHOLD,
                'size_mb' => round(self::DOWNLOAD_SIZE_THRESHOLD / 1024 / 1024, 2),
            ],
        ];
    }
    
    /**
     * Track download activity
     * 
     * @param int $userId
     * @param string $resourceType
     * @param int $sizeBytes
     * @param array $metadata
     * @return void
     */
    public function trackDownloadActivity(int $userId, string $resourceType, int $sizeBytes, array $metadata = []): void
    {
        $cacheKey = "download_activity:{$userId}";
        $history = Cache::get($cacheKey, []);
        
        $history[] = [
            'resource_type' => $resourceType,
            'size_bytes' => $sizeBytes,
            'timestamp' => now()->timestamp,
            'metadata' => $metadata,
        ];
        
        // Keep last 50 downloads
        $history = array_slice($history, -50);
        
        // Cache for 24 hours
        Cache::put($cacheKey, $history, 86400);
    }
    
    /**
     * Detect search enumeration patterns
     * 
     * @param User $user
     * @return array Search enumeration detection
     */
    public function detectSearchEnumeration(User $user): array
    {
        $cacheKey = "search_patterns:{$user->id}";
        $searchHistory = Cache::get($cacheKey, []);
        
        $lastHour = now()->subHour()->timestamp;
        $recentSearches = collect($searchHistory)->where('timestamp', '>', $lastHour);
        
        $searchCount = $recentSearches->count();
        $uniqueTerms = $recentSearches->pluck('search_term')->unique()->count();
        $systematicPattern = $this->detectSystematicSearchPattern($recentSearches);
        
        $detected = $searchCount > self::SEARCH_PATTERN_THRESHOLD || 
                   $systematicPattern['detected'];
        
        return [
            'indicator_type' => 'search_enumeration',
            'detected' => $detected,
            'search_count_hour' => $searchCount,
            'unique_terms' => $uniqueTerms,
            'systematic_pattern' => $systematicPattern,
            'threshold' => self::SEARCH_PATTERN_THRESHOLD,
            'severity' => $detected ? 'medium' : 'low',
        ];
    }
    
    /**
     * Track search activity for pattern analysis
     * 
     * @param int $userId
     * @param string $searchTerm
     * @param int $resultCount
     * @return void
     */
    public function trackSearchActivity(int $userId, string $searchTerm, int $resultCount = 0): void
    {
        $cacheKey = "search_patterns:{$userId}";
        $history = Cache::get($cacheKey, []);
        
        $history[] = [
            'search_term' => $searchTerm,
            'result_count' => $resultCount,
            'timestamp' => now()->timestamp,
        ];
        
        // Keep last 100 searches
        $history = array_slice($history, -100);
        
        // Cache for 4 hours
        Cache::put($cacheKey, $history, 14400);
    }
    
    /**
     * Detect data scraping patterns
     * 
     * @param User $user
     * @param Request $request
     * @return array Scraping detection result
     */
    public function detectDataScraping(User $user, Request $request): array
    {
        $userAgent = $request->userAgent() ?? '';
        $requestPattern = $request->getPathInfo();
        
        // Check for automated tools patterns
        $automatedTools = [
            '/curl/i', '/wget/i', '/python/i', '/postman/i', 
            '/insomnia/i', '/scrapy/i', '/beautifulsoup/i'
        ];
        
        $toolDetected = false;
        foreach ($automatedTools as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                $toolDetected = true;
                break;
            }
        }
        
        // Check request patterns that suggest scraping
        $scrapingPatterns = [
            'sequential_ids' => $this->detectSequentialIDRequests($user),
            'systematic_browsing' => $this->detectSystematicBrowsing($user),
            'no_user_interaction' => $this->detectLackOfUserInteraction($user),
        ];
        
        $patternScore = array_sum(array_map(fn($p) => $p ? 1 : 0, $scrapingPatterns));
        $detected = $toolDetected || $patternScore >= 2;
        
        return [
            'indicator_type' => 'data_scraping',
            'detected' => $detected,
            'automated_tool_detected' => $toolDetected,
            'scraping_patterns' => $scrapingPatterns,
            'pattern_score' => $patternScore,
            'user_agent' => $userAgent,
            'severity' => $detected ? 'high' : 'low',
        ];
    }
    
    /**
     * Detect API abuse for data extraction
     * 
     * @param User $user
     * @param Request $request
     * @return array API abuse detection
     */
    public function detectAPIDataAbuse(User $user, Request $request): array
    {
        $isApiRequest = str_starts_with($request->getPathInfo(), '/api/');
        
        if (!$isApiRequest) {
            return ['indicator_type' => 'api_data_abuse', 'detected' => false];
        }
        
        $cacheKey = "api_usage:{$user->id}";
        $apiHistory = Cache::get($cacheKey, []);
        
        $lastHour = now()->subHour()->timestamp;
        $recentAPICalls = collect($apiHistory)->where('timestamp', '>', $lastHour);
        
        $callCount = $recentAPICalls->count();
        $uniqueEndpoints = $recentAPICalls->pluck('endpoint')->unique()->count();
        $dataVolume = $recentAPICalls->sum('response_size');
        
        // Detect abuse patterns
        $highFrequency = $callCount > 200; // 200 API calls per hour
        $endpointEnumeration = $uniqueEndpoints > 20;
        $massDataRetrieval = $dataVolume > (20 * 1024 * 1024); // 20MB
        
        $detected = $highFrequency || $endpointEnumeration || $massDataRetrieval;
        
        return [
            'indicator_type' => 'api_data_abuse',
            'detected' => $detected,
            'call_count_hour' => $callCount,
            'unique_endpoints' => $uniqueEndpoints,
            'data_volume_mb' => round($dataVolume / 1024 / 1024, 2),
            'abuse_indicators' => [
                'high_frequency' => $highFrequency,
                'endpoint_enumeration' => $endpointEnumeration,
                'mass_data_retrieval' => $massDataRetrieval,
            ],
            'severity' => $detected ? 'high' : 'low',
        ];
    }
    
    /**
     * Track API usage for abuse detection
     * 
     * @param int $userId
     * @param string $endpoint
     * @param int $responseSize
     * @param int $statusCode
     * @return void
     */
    public function trackAPIUsage(int $userId, string $endpoint, int $responseSize, int $statusCode): void
    {
        $cacheKey = "api_usage:{$userId}";
        $history = Cache::get($cacheKey, []);
        
        $history[] = [
            'endpoint' => $endpoint,
            'response_size' => $responseSize,
            'status_code' => $statusCode,
            'timestamp' => now()->timestamp,
        ];
        
        // Keep last 500 API calls
        $history = array_slice($history, -500);
        
        // Cache for 6 hours
        Cache::put($cacheKey, $history, 21600);
    }
    
    /**
     * Detect database enumeration attempts
     * 
     * @param User $user
     * @param Request $request
     * @return array Database enumeration detection
     */
    public function detectDatabaseEnumeration(User $user, Request $request): array
    {
        // Check for SQL injection patterns in request
        $sqlPatterns = [
            '/union\s+select/i',
            '/information_schema/i',
            '/show\s+tables/i',
            '/describe\s+/i',
            '/(and|or)\s+1=1/i',
            '/having\s+/i',
            '/group\s+by\s+/i'
        ];
        
        $sqlAttempt = false;
        $requestContent = $request->getPathInfo() . '?' . $request->getQueryString();
        
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $requestContent)) {
                $sqlAttempt = true;
                break;
            }
        }
        
        // Check for systematic data access patterns
        $systematicAccess = $this->detectSystematicDataAccess($user);
        
        $detected = $sqlAttempt || $systematicAccess;
        
        if ($detected) {
            Log::channel('security')->critical('Database Enumeration Detected', [
                'user_id' => $user->id,
                'sql_attempt' => $sqlAttempt,
                'systematic_access' => $systematicAccess,
                'request_content' => $requestContent,
            ]);
        }
        
        return [
            'indicator_type' => 'database_enumeration',
            'detected' => $detected,
            'sql_injection_attempt' => $sqlAttempt,
            'systematic_access_pattern' => $systematicAccess,
            'severity' => $detected ? 'critical' : 'low',
        ];
    }
    
    // Helper methods
    private function analyzeTimeDistribution($accessLog): array
    {
        return $accessLog->groupBy(function ($access) {
            return Carbon::createFromTimestamp($access['timestamp'])->format('H');
        })->map->count()->toArray();
    }
    
    private function detectSystematicSearchPattern($searches): array
    {
        // Look for alphabetical or numerical progression in search terms
        $terms = $searches->pluck('search_term')->toArray();
        
        $alphabetical = $this->isAlphabeticalProgression($terms);
        $numerical = $this->isNumericalProgression($terms);
        $wildcard = $this->hasWildcardPattern($terms);
        
        return [
            'detected' => $alphabetical || $numerical || $wildcard,
            'patterns' => [
                'alphabetical_progression' => $alphabetical,
                'numerical_progression' => $numerical,
                'wildcard_usage' => $wildcard,
            ],
        ];
    }
    
    private function isAlphabeticalProgression(array $terms): bool
    {
        if (count($terms) < 5) return false;
        
        // Simple check for consecutive letters
        $firstLetters = array_map(fn($term) => strtolower(substr($term, 0, 1)), $terms);
        $consecutive = 0;
        
        for ($i = 1; $i < count($firstLetters); $i++) {
            if (ord($firstLetters[$i]) === ord($firstLetters[$i-1]) + 1) {
                $consecutive++;
            }
        }
        
        return $consecutive >= 4;
    }
    
    private function isNumericalProgression(array $terms): bool
    {
        if (count($terms) < 5) return false;
        
        $numbers = array_filter(array_map('intval', $terms));
        if (count($numbers) < 5) return false;
        
        sort($numbers);
        $consecutive = 0;
        
        for ($i = 1; $i < count($numbers); $i++) {
            if ($numbers[$i] === $numbers[$i-1] + 1) {
                $consecutive++;
            }
        }
        
        return $consecutive >= 4;
    }
    
    private function hasWildcardPattern(array $terms): bool
    {
        $wildcardCount = 0;
        foreach ($terms as $term) {
            if (str_contains($term, '*') || str_contains($term, '%') || str_contains($term, '?')) {
                $wildcardCount++;
            }
        }
        
        return $wildcardCount > (count($terms) * 0.3); // 30% wildcard usage
    }
    
    private function isDownloadRequest(Request $request, array $responseContext): bool
    {
        $downloadIndicators = [
            str_contains($request->getPathInfo(), '/download'),
            str_contains($request->getPathInfo(), '/export'),
            str_contains($request->getPathInfo(), '/backup'),
            isset($responseContext['content_disposition']) && str_contains($responseContext['content_disposition'], 'attachment'),
        ];
        
        return in_array(true, $downloadIndicators);
    }
    
    private function detectSequentialIDRequests(User $user): bool
    {
        // Check if user is requesting resources with sequential IDs
        $cacheKey = "id_pattern:{$user->id}";
        $idHistory = Cache::get($cacheKey, []);
        
        if (count($idHistory) < 10) return false;
        
        $ids = array_map('intval', array_filter($idHistory, 'is_numeric'));
        sort($ids);
        
        $sequential = 0;
        for ($i = 1; $i < count($ids); $i++) {
            if ($ids[$i] === $ids[$i-1] + 1) {
                $sequential++;
            }
        }
        
        return $sequential >= 8; // 8 or more sequential IDs
    }
    
    private function detectSystematicBrowsing(User $user): bool
    {
        // Check for systematic browsing patterns (pagination, category enumeration)
        $cacheKey = "browse_pattern:{$user->id}";
        $browseHistory = Cache::get($cacheKey, []);
        
        if (count($browseHistory) < 20) return false;
        
        // Look for pagination patterns
        $paginationCount = 0;
        foreach ($browseHistory as $url) {
            if (preg_match('/page=\d+|offset=\d+|limit=\d+/', $url)) {
                $paginationCount++;
            }
        }
        
        return $paginationCount > (count($browseHistory) * 0.5);
    }
    
    private function detectLackOfUserInteraction(User $user): bool
    {
        // Check for lack of typical user interactions (no clicks, form submissions, etc.)
        $cacheKey = "interaction_pattern:{$user->id}";
        $interactions = Cache::get($cacheKey, []);
        
        $totalRequests = count($interactions);
        $interactiveActions = array_filter($interactions, function($action) {
            return in_array($action['type'], ['click', 'form_submit', 'search', 'comment']);
        });
        
        if ($totalRequests < 10) return false;
        
        $interactionRatio = count($interactiveActions) / $totalRequests;
        return $interactionRatio < 0.1; // Less than 10% interactive actions
    }
    
    private function detectSystematicDataAccess(User $user): bool
    {
        // Check for systematic data access patterns
        $accessHistory = Cache::get("data_access_volume:{$user->id}", []);
        
        if (count($accessHistory) < 20) return false;
        
        // Look for regular intervals between accesses
        $timestamps = array_column($accessHistory, 'timestamp');
        sort($timestamps);
        
        $intervals = [];
        for ($i = 1; $i < count($timestamps); $i++) {
            $intervals[] = $timestamps[$i] - $timestamps[$i-1];
        }
        
        // Check for consistent intervals (automation indicator)
        $averageInterval = array_sum($intervals) / count($intervals);
        $consistentIntervals = 0;
        
        foreach ($intervals as $interval) {
            if (abs($interval - $averageInterval) < 5) { // Within 5 seconds
                $consistentIntervals++;
            }
        }
        
        return $consistentIntervals > (count($intervals) * 0.7); // 70% consistent timing
    }
    
    private function getRiskLevel(int $score): string
    {
        if ($score >= 80) return 'critical';
        if ($score >= 60) return 'high';
        if ($score >= 40) return 'medium';
        if ($score >= 20) return 'low';
        return 'minimal';
    }
    
    private function generateExfiltrationRecommendations(array $analysis): array
    {
        $recommendations = [];
        
        if ($analysis['risk_score'] >= 70) {
            $recommendations[] = 'Immediately suspend user access';
            $recommendations[] = 'Review all recent data access activities';
            $recommendations[] = 'Check for unauthorized data exports';
            $recommendations[] = 'Alert security team immediately';
        } elseif ($analysis['risk_score'] >= 40) {
            $recommendations[] = 'Increase monitoring level for this user';
            $recommendations[] = 'Implement additional data access controls';
            $recommendations[] = 'Review user permissions and access levels';
        }
        
        foreach ($analysis['exfiltration_indicators'] as $indicator) {
            switch ($indicator['indicator_type']) {
                case 'mass_data_access':
                    $recommendations[] = 'Implement data access quotas';
                    break;
                case 'suspicious_downloads':
                    $recommendations[] = 'Monitor download activities closely';
                    break;
                case 'api_data_abuse':
                    $recommendations[] = 'Implement API rate limiting';
                    break;
                case 'database_enumeration':
                    $recommendations[] = 'Check database security immediately';
                    break;
            }
        }
        
        return array_unique($recommendations);
    }
}