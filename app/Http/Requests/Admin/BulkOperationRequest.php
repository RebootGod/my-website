<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ========================================
 * BULK OPERATION REQUEST VALIDATION
 * Enhanced validation for bulk operations
 * ========================================
 */
class BulkOperationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only allow admins and moderators to perform bulk operations
        return $this->user() && $this->user()->canManage('member');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $resourceType = $this->route('resource'); // movies, users, series

        return [
            'action' => [
                'required',
                'string',
                Rule::in($this->getAllowedActions($resourceType))
            ],
            'items' => [
                'required',
                'array',
                'min:1',
                'max:100' // Limit bulk operations to 100 items
            ],
            'items.*' => [
                'integer',
                'min:1',
                $this->getExistsRule($resourceType)
            ],
            // Optional parameters for specific actions
            'quality' => [
                'sometimes',
                'required_if:action,update_quality',
                'string',
                Rule::in(['360p', '480p', '720p', '1080p', '4K'])
            ],
            'role' => [
                'sometimes',
                'required_if:action,change_role',
                'string',
                Rule::in(['member', 'moderator', 'admin'])
            ],
            'status' => [
                'sometimes',
                'required_if:action,change_status',
                'string',
                Rule::in(['active', 'inactive', 'banned'])
            ]
        ];
    }

    /**
     * Get allowed actions for resource type
     */
    private function getAllowedActions(string $resourceType): array
    {
        $actions = [
            'movies' => [
                'publish',
                'draft',
                'archive',
                'delete',
                'update_quality'
            ],
            'series' => [
                'publish',
                'draft',
                'archive',
                'delete'
            ],
            'users' => [
                'activate',
                'deactivate',
                'promote',
                'demote',
                'delete',
                'change_role',
                'change_status'
            ],
            'invite_codes' => [
                'activate',
                'deactivate',
                'delete'
            ]
        ];

        return $actions[$resourceType] ?? [];
    }

    /**
     * Get exists validation rule for resource type
     */
    private function getExistsRule(string $resourceType): string
    {
        $tables = [
            'movies' => 'movies,id',
            'series' => 'series,id',
            'users' => 'users,id',
            'invite_codes' => 'invite_codes,id'
        ];

        return 'exists:' . ($tables[$resourceType] ?? 'movies,id');
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Please specify an action to perform.',
            'action.in' => 'The selected action is not allowed for this resource type.',
            'items.required' => 'Please select at least one item.',
            'items.min' => 'Please select at least one item.',
            'items.max' => 'You can only perform bulk operations on up to 100 items at once.',
            'items.*.exists' => 'One or more selected items no longer exist.',
            'quality.required_if' => 'Quality is required when updating quality.',
            'role.required_if' => 'Role is required when changing user roles.',
            'status.required_if' => 'Status is required when changing user status.'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Additional security checks
            $this->validateSecurityConstraints($validator);
            $this->validateBusinessRules($validator);
        });
    }

    /**
     * Validate security constraints
     */
    private function validateSecurityConstraints($validator): void
    {
        $resourceType = $this->route('resource');
        $action = $this->input('action');
        $items = $this->input('items', []);

        // Prevent self-modification for user operations
        if ($resourceType === 'users' && in_array($this->user()->id, $items)) {
            $validator->errors()->add('items', 'You cannot perform bulk operations on your own account.');
        }

        // Prevent operations on super admin users
        if ($resourceType === 'users') {
            $superAdminIds = \App\Models\User::whereIn('id', $items)
                ->where('role', 'super_admin')
                ->pluck('id')
                ->toArray();

            if (!empty($superAdminIds)) {
                $validator->errors()->add('items', 'You cannot perform bulk operations on super admin accounts.');
            }
        }

        // Rate limiting check for destructive actions
        if (in_array($action, ['delete', 'ban'])) {
            $cacheKey = 'bulk_destructive_' . $this->user()->id;
            $attempts = cache()->get($cacheKey, 0);

            if ($attempts >= 5) {
                $validator->errors()->add('action', 'Too many destructive operations. Please wait before trying again.');
            } else {
                cache()->put($cacheKey, $attempts + 1, now()->addMinutes(15));
            }
        }
    }

    /**
     * Validate business rules
     */
    private function validateBusinessRules($validator): void
    {
        $resourceType = $this->route('resource');
        $action = $this->input('action');
        $items = $this->input('items', []);

        // Content-specific validations
        if ($resourceType === 'movies' || $resourceType === 'series') {
            // Don't allow archiving already published content without confirmation
            if ($action === 'archive') {
                $publishedCount = \App\Models\Movie::whereIn('id', $items)
                    ->where('status', 'published')
                    ->count();

                if ($publishedCount > 0 && !$this->has('confirm_archive')) {
                    $validator->errors()->add('action', 'This will archive published content. Please confirm this action.');
                }
            }
        }

        // User-specific validations
        if ($resourceType === 'users') {
            // Validate role changes based on current user permissions
            if (in_array($action, ['promote', 'demote', 'change_role'])) {
                $currentUserRole = $this->user()->role;

                // Only super_admin can promote to admin
                if ($action === 'promote' && $currentUserRole !== 'super_admin') {
                    $validator->errors()->add('action', 'You do not have permission to promote users to admin level.');
                }

                // Only admin+ can change roles
                if ($action === 'change_role' && !in_array($currentUserRole, ['admin', 'super_admin'])) {
                    $validator->errors()->add('action', 'You do not have permission to change user roles.');
                }
            }
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize items array
        if ($this->has('items')) {
            $items = array_filter(
                array_map('intval', (array) $this->input('items')),
                function ($item) {
                    return $item > 0;
                }
            );

            $this->merge([
                'items' => array_unique($items)
            ]);
        }

        // Normalize action
        if ($this->has('action')) {
            $this->merge([
                'action' => strtolower(trim($this->input('action')))
            ]);
        }
    }
}