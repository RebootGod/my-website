<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserBulkActionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization will be handled in the controller via UserPermissionService
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => 'required|in:ban,unban,delete,change_role,activate,suspend',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'new_role' => 'required_if:action,change_role|in:user,admin,moderator,super_admin',
            'ban_reason' => 'required_if:action,ban|string|max:500',
            'confirm_delete' => 'required_if:action,delete|accepted',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Please select an action to perform.',
            'action.in' => 'Invalid action selected.',
            'user_ids.required' => 'Please select at least one user.',
            'user_ids.min' => 'Please select at least one user.',
            'user_ids.*.exists' => 'One or more selected users are invalid.',
            'new_role.required_if' => 'New role is required for role change action.',
            'new_role.in' => 'Invalid role selected.',
            'ban_reason.required_if' => 'Ban reason is required when banning users.',
            'ban_reason.max' => 'Ban reason cannot exceed 500 characters.',
            'confirm_delete.required_if' => 'Please confirm the deletion action.',
            'confirm_delete.accepted' => 'You must confirm the deletion action.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'user_ids' => 'selected users',
            'new_role' => 'new role',
            'ban_reason' => 'ban reason',
            'confirm_delete' => 'delete confirmation',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Remove current user from user_ids to prevent self-action
        if ($this->has('user_ids') && is_array($this->user_ids)) {
            $currentUserId = auth()->id();
            $filteredUserIds = array_filter($this->user_ids, function($id) use ($currentUserId) {
                return $id != $currentUserId;
            });
            
            $this->merge([
                'user_ids' => array_values($filteredUserIds),
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Additional custom validation
            if ($this->action === 'delete') {
                $this->validateDeleteAction($validator);
            }
            
            if ($this->action === 'change_role') {
                $this->validateRoleChangeAction($validator);
            }
        });
    }

    /**
     * Validate delete action specific rules.
     */
    private function validateDeleteAction($validator): void
    {
        if (empty($this->user_ids)) {
            return;
        }

        // Check if trying to delete all admins
        $adminCount = \App\Models\User::where('role', 'admin')->count();
        $adminsToDelete = \App\Models\User::whereIn('id', $this->user_ids)
            ->where('role', 'admin')
            ->count();
        
        if ($adminCount - $adminsToDelete < 1) {
            $validator->errors()->add('user_ids', 'Cannot delete all admin users. At least one admin must remain.');
        }
    }

    /**
     * Validate role change action specific rules.
     */
    private function validateRoleChangeAction($validator): void
    {
        if (!$this->new_role || empty($this->user_ids)) {
            return;
        }

        $currentUser = auth()->user();
        $currentUserLevel = $currentUser->getHierarchyLevel();
        
        $newRoleLevel = match($this->new_role) {
            'super_admin' => 100,
            'admin' => 80,
            'moderator' => 60,
            'user' => 0,
            default => 0
        };

        if ($newRoleLevel >= $currentUserLevel) {
            $validator->errors()->add('new_role', 'You cannot assign a role equal to or higher than your own.');
        }
    }
}
