<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Services\Admin\UserActionLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;
use Carbon\Carbon;

/**
 * UserExportService - Handles all user data export functionality
 * Supports multiple formats: CSV, Excel, JSON, PDF
 */
class UserExportService
{
    /**
     * Export users to CSV format
     */
    public static function exportToCsv(array $filters = [], array $columns = null): array
    {
        try {
            $users = self::getUsersForExport($filters);
            $columns = $columns ?? self::getDefaultColumns();
            
            $filename = 'users_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
            $filepath = 'exports/' . $filename;
            
            // Create CSV content
            $csvContent = self::generateCsvContent($users, $columns);
            
            // Store file
            Storage::disk('public')->put($filepath, $csvContent);
            
            $fileSize = Storage::disk('public')->size($filepath);
            
            // Log export action
            UserActionLogger::logUserExport('CSV', $users->count(), $filters, [
                'filename' => $filename,
                'file_size' => $fileSize,
                'columns_exported' => $columns,
            ]);
            
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'download_url' => Storage::disk('public')->url($filepath),
                'file_size' => $fileSize,
                'total_records' => $users->count(),
                'message' => 'CSV export completed successfully',
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to export CSV: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Export users to JSON format
     */
    public static function exportToJson(array $filters = [], array $columns = null): array
    {
        try {
            $users = self::getUsersForExport($filters);
            $columns = $columns ?? self::getDefaultColumns();
            
            $filename = 'users_export_' . now()->format('Y-m-d_H-i-s') . '.json';
            $filepath = 'exports/' . $filename;
            
            // Prepare data for JSON export
            $jsonData = [
                'export_info' => [
                    'exported_at' => now()->toISOString(),
                    'exported_by' => auth()->user()?->username,
                    'total_records' => $users->count(),
                    'filters_applied' => $filters,
                    'columns' => $columns,
                ],
                'users' => $users->map(function ($user) use ($columns) {
                    return self::formatUserForExport($user, $columns);
                })->toArray(),
            ];
            
            $jsonContent = json_encode($jsonData, JSON_PRETTY_PRINT);
            
            // Store file
            Storage::disk('public')->put($filepath, $jsonContent);
            
            $fileSize = Storage::disk('public')->size($filepath);
            
            // Log export action
            UserActionLogger::logUserExport('JSON', $users->count(), $filters, [
                'filename' => $filename,
                'file_size' => $fileSize,
                'columns_exported' => $columns,
            ]);
            
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'download_url' => Storage::disk('public')->url($filepath),
                'file_size' => $fileSize,
                'total_records' => $users->count(),
                'message' => 'JSON export completed successfully',
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to export JSON: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Export users to Excel format (placeholder)
     */
    public static function exportToExcel(array $filters = [], array $columns = null): array
    {
        // TODO: Implement Excel export using Laravel Excel package
        return [
            'success' => false,
            'message' => 'Excel export functionality will be implemented with Laravel Excel package.',
        ];
    }

    /**
     * Export users to PDF format (placeholder)
     */
    public static function exportToPdf(array $filters = [], array $columns = null): array
    {
        // TODO: Implement PDF export using DomPDF or similar
        return [
            'success' => false,
            'message' => 'PDF export functionality will be implemented with DomPDF package.',
        ];
    }

    /**
     * Get users for export based on filters
     */
    private static function getUsersForExport(array $filters = []): Collection
    {
        $query = User::with(['role']);
        
        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }
        
        // Apply role filter
        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }
        
        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        // Apply date range filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        
        // Apply sorting
        $sortBy = $filters['sort'] ?? 'created_at';
        $sortOrder = $filters['order'] ?? 'desc';
        
        $allowedSorts = ['username', 'email', 'name', 'role', 'status', 'created_at', 'last_login_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }
        
        // Apply limit if specified
        if (!empty($filters['limit']) && is_numeric($filters['limit'])) {
            $query->limit($filters['limit']);
        }
        
        return $query->get();
    }

    /**
     * Get default columns for export
     */
    private static function getDefaultColumns(): array
    {
        return [
            'id',
            'username',
            'name',
            'email',
            'role',
            'status',
            'created_at',
            'updated_at',
            'last_login_at',
        ];
    }

    /**
     * Get all available columns for export
     */
    public static function getAvailableColumns(): array
    {
        return [
            'id' => 'User ID',
            'username' => 'Username',
            'name' => 'Full Name',
            'email' => 'Email Address',
            'role' => 'User Role',
            'status' => 'Account Status',
            'created_at' => 'Registration Date',
            'updated_at' => 'Last Updated',
            'last_login_at' => 'Last Login',
            'email_verified_at' => 'Email Verified',
            // Extended columns
            'movie_views_count' => 'Total Movie Views',
            'registration_ip' => 'Registration IP',
            'invite_code_used' => 'Invite Code Used',
            'total_reports' => 'Reports Submitted',
        ];
    }

    /**
     * Format user data for export
     */
    private static function formatUserForExport(User $user, array $columns): array
    {
        $data = [];
        
        foreach ($columns as $column) {
            switch ($column) {
                case 'id':
                    $data['id'] = $user->id;
                    break;
                case 'username':
                    $data['username'] = $user->username;
                    break;
                case 'name':
                    $data['name'] = $user->name;
                    break;
                case 'email':
                    $data['email'] = $user->email;
                    break;
                case 'role':
                    $data['role'] = ucfirst($user->role);
                    break;
                case 'status':
                    $data['status'] = ucfirst($user->status);
                    break;
                case 'created_at':
                    $data['created_at'] = $user->created_at?->format('Y-m-d H:i:s');
                    break;
                case 'updated_at':
                    $data['updated_at'] = $user->updated_at?->format('Y-m-d H:i:s');
                    break;
                case 'last_login_at':
                    $data['last_login_at'] = $user->last_login_at?->format('Y-m-d H:i:s') ?? 'Never';
                    break;
                case 'email_verified_at':
                    $data['email_verified_at'] = $user->email_verified_at?->format('Y-m-d H:i:s') ?? 'Not Verified';
                    break;
                case 'movie_views_count':
                    $data['movie_views_count'] = $user->movieViews?->count() ?? 0;
                    break;
                case 'registration_ip':
                    $data['registration_ip'] = $user->registration?->ip_address ?? 'Unknown';
                    break;
                case 'invite_code_used':
                    $data['invite_code_used'] = $user->registration?->inviteCode?->code ?? 'None';
                    break;
                case 'total_reports':
                    $data['total_reports'] = $user->brokenLinkReports?->count() ?? 0;
                    break;
                default:
                    $data[$column] = $user->{$column} ?? '';
            }
        }
        
        return $data;
    }

    /**
     * Generate CSV content from users data
     */
    private static function generateCsvContent(Collection $users, array $columns): string
    {
        $csvData = [];
        
        // Add header row
        $headers = array_map(function($column) {
            return self::getAvailableColumns()[$column] ?? ucfirst(str_replace('_', ' ', $column));
        }, $columns);
        
        $csvData[] = $headers;
        
        // Add data rows
        foreach ($users as $user) {
            $row = [];
            $userData = self::formatUserForExport($user, $columns);
            
            foreach ($columns as $column) {
                $row[] = $userData[$column] ?? '';
            }
            
            $csvData[] = $row;
        }
        
        // Convert to CSV string
        $output = '';
        foreach ($csvData as $row) {
            $output .= '"' . implode('","', array_map('addslashes', $row)) . '"' . "\n";
        }
        
        return $output;
    }

    /**
     * Get export statistics
     */
    public static function getExportStats(): array
    {
        $exportFiles = Storage::disk('public')->files('exports');
        
        return [
            'total_exports' => count($exportFiles),
            'export_formats' => ['CSV', 'JSON', 'Excel (Coming Soon)', 'PDF (Coming Soon)'],
            'available_columns' => count(self::getAvailableColumns()),
            'last_export' => !empty($exportFiles) ? 
                Carbon::createFromTimestamp(Storage::disk('public')->lastModified(collect($exportFiles)->last())) : null,
            'total_export_size' => array_sum(array_map(function($file) {
                return Storage::disk('public')->size($file);
            }, $exportFiles)),
        ];
    }

    /**
     * Clean up old export files
     */
    public static function cleanupOldExports(int $daysOld = 7): array
    {
        $exportFiles = Storage::disk('public')->files('exports');
        $deletedFiles = [];
        $cutoffTime = now()->subDays($daysOld)->timestamp;
        
        foreach ($exportFiles as $file) {
            $lastModified = Storage::disk('public')->lastModified($file);
            
            if ($lastModified < $cutoffTime) {
                Storage::disk('public')->delete($file);
                $deletedFiles[] = $file;
            }
        }
        
        return [
            'deleted_count' => count($deletedFiles),
            'deleted_files' => $deletedFiles,
            'remaining_files' => count($exportFiles) - count($deletedFiles),
        ];
    }

    /**
     * Validate export parameters
     */
    public static function validateExportParams(array $params): array
    {
        $errors = [];
        
        // Validate format
        if (empty($params['format'])) {
            $errors[] = 'Export format is required';
        } elseif (!in_array($params['format'], ['csv', 'json', 'excel', 'pdf'])) {
            $errors[] = 'Invalid export format';
        }
        
        // Validate columns
        if (!empty($params['columns'])) {
            $availableColumns = array_keys(self::getAvailableColumns());
            $invalidColumns = array_diff($params['columns'], $availableColumns);
            
            if (!empty($invalidColumns)) {
                $errors[] = 'Invalid columns: ' . implode(', ', $invalidColumns);
            }
        }
        
        // Validate date range
        if (!empty($params['date_from']) && !empty($params['date_to'])) {
            try {
                $dateFrom = Carbon::parse($params['date_from']);
                $dateTo = Carbon::parse($params['date_to']);
                
                if ($dateFrom > $dateTo) {
                    $errors[] = 'Date from cannot be later than date to';
                }
            } catch (\Exception $e) {
                $errors[] = 'Invalid date format';
            }
        }
        
        // Validate limit
        if (!empty($params['limit'])) {
            if (!is_numeric($params['limit']) || $params['limit'] < 1 || $params['limit'] > 10000) {
                $errors[] = 'Limit must be a number between 1 and 10,000';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get supported export formats
     */
    public static function getSupportedFormats(): array
    {
        return [
            'csv' => [
                'name' => 'CSV',
                'description' => 'Comma-separated values file',
                'extension' => '.csv',
                'mime_type' => 'text/csv',
                'available' => true,
            ],
            'json' => [
                'name' => 'JSON',
                'description' => 'JavaScript Object Notation file',
                'extension' => '.json',
                'mime_type' => 'application/json',
                'available' => true,
            ],
            'excel' => [
                'name' => 'Excel',
                'description' => 'Microsoft Excel spreadsheet',
                'extension' => '.xlsx',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'available' => false,
                'note' => 'Requires Laravel Excel package',
            ],
            'pdf' => [
                'name' => 'PDF',
                'description' => 'Portable Document Format',
                'extension' => '.pdf',
                'mime_type' => 'application/pdf',
                'available' => false,
                'note' => 'Requires DomPDF package',
            ],
        ];
    }
}