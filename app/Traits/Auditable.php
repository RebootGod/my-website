<?php

namespace App\Traits;

use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    /**
     * Boot the auditable trait for a model.
     */
    public static function bootAuditable()
    {
        static::created(function ($model) {
            $model->auditCreated();
        });

        static::updated(function ($model) {
            $model->auditUpdated();
        });

        static::deleted(function ($model) {
            $model->auditDeleted();
        });
    }

    /**
     * Log model creation
     */
    protected function auditCreated()
    {
        if (!$this->shouldAudit('created')) {
            return;
        }

        AuditLogger::log(
            'created',
            $this->getAuditDescription('created'),
            $this,
            null,
            $this->getAuditableAttributes()
        );
    }

    /**
     * Log model updates
     */
    protected function auditUpdated()
    {
        if (!$this->shouldAudit('updated') || !$this->wasChanged()) {
            return;
        }

        $old = [];
        $new = [];

        foreach ($this->getDirty() as $key => $value) {
            if ($this->isAuditableAttribute($key)) {
                $old[$key] = $this->getOriginal($key);
                $new[$key] = $value;
            }
        }

        if (empty($old)) {
            return;
        }

        AuditLogger::log(
            'updated',
            $this->getAuditDescription('updated'),
            $this,
            $old,
            $new
        );
    }

    /**
     * Log model deletion
     */
    protected function auditDeleted()
    {
        if (!$this->shouldAudit('deleted')) {
            return;
        }

        AuditLogger::log(
            'deleted',
            $this->getAuditDescription('deleted'),
            $this,
            $this->getAuditableAttributes(),
            null
        );
    }

    /**
     * Get auditable attributes
     */
    protected function getAuditableAttributes()
    {
        $attributes = $this->toArray();

        // Remove sensitive attributes
        $hidden = array_merge(
            $this->getHidden(),
            ['password', 'remember_token', 'api_token']
        );

        foreach ($hidden as $key) {
            unset($attributes[$key]);
        }

        return $attributes;
    }

    /**
     * Check if attribute should be audited
     */
    protected function isAuditableAttribute($key)
    {
        // Skip certain attributes
        $skipAttributes = [
            'updated_at',
            'created_at',
            'password',
            'remember_token',
            'api_token',
            'email_verified_at'
        ];

        if (in_array($key, $skipAttributes)) {
            return false;
        }

        // If model has auditableAttributes property, use it
        if (property_exists($this, 'auditableAttributes')) {
            return in_array($key, $this->auditableAttributes);
        }

        // If model has nonAuditableAttributes property, exclude those
        if (property_exists($this, 'nonAuditableAttributes')) {
            return !in_array($key, $this->nonAuditableAttributes);
        }

        return true;
    }

    /**
     * Check if action should be audited
     */
    protected function shouldAudit($action)
    {
        // Skip if no authenticated user (for system operations)
        if (!Auth::check()) {
            return false;
        }

        // If model has auditableActions property, use it
        if (property_exists($this, 'auditableActions')) {
            return in_array($action, $this->auditableActions);
        }

        return true;
    }

    /**
     * Get audit description for action
     */
    protected function getAuditDescription($action)
    {
        $modelName = class_basename($this);
        $identifier = $this->getAuditIdentifier();

        return match($action) {
            'created' => "Created {$modelName}: {$identifier}",
            'updated' => "Updated {$modelName}: {$identifier}",
            'deleted' => "Deleted {$modelName}: {$identifier}",
            default => "{$action} {$modelName}: {$identifier}"
        };
    }

    /**
     * Get identifier for audit log
     */
    protected function getAuditIdentifier()
    {
        // Try common identifier attributes
        foreach (['title', 'name', 'username', 'email', 'slug'] as $attr) {
            if (isset($this->attributes[$attr])) {
                return $this->attributes[$attr];
            }
        }

        return $this->getKey();
    }
}