<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;

/**
 * Enterprise Activity Logging Trait
 * Provides comprehensive audit trail functionality for critical operations
 */
trait EnterpriseActivityLogger
{
    /**
     * Log a user activity
     *
     * @param string $action
     * @param string $description
     * @param array $metadata
     * @param string $level
     * @return void
     */
    protected function logActivity(
        string $action,
        string $description,
        array $metadata = [],
        string $level = 'info'
    ): void {
        $user = auth()->user();
        $agent = new Agent();

        $logData = [
            'timestamp' => now()->toIso8601String(),
            'action' => $action,
            'description' => $description,
            'user' => $user ? [
                'id' => $user->id,
                'email' => $user->email,
                'type' => get_class($user),
            ] : null,
            'request' => [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'method' => request()->method(),
                'url' => request()->fullUrl(),
                'route' => optional(request()->route())->getName(),
            ],
            'device' => [
                'platform' => $agent->platform(),
                'browser' => $agent->browser(),
                'device' => $agent->device(),
                'is_mobile' => $agent->isMobile(),
                'is_desktop' => $agent->isDesktop(),
            ],
            'metadata' => $metadata,
            'session_id' => session()->getId(),
        ];

        // Log based on level
        match ($level) {
            'emergency' => Log::emergency('Activity Log: ' . $action, $logData),
            'alert' => Log::alert('Activity Log: ' . $action, $logData),
            'critical' => Log::critical('Activity Log: ' . $action, $logData),
            'error' => Log::error('Activity Log: ' . $action, $logData),
            'warning' => Log::warning('Activity Log: ' . $action, $logData),
            'notice' => Log::notice('Activity Log: ' . $action, $logData),
            'info' => Log::info('Activity Log: ' . $action, $logData),
            'debug' => Log::debug('Activity Log: ' . $action, $logData),
            default => Log::info('Activity Log: ' . $action, $logData),
        };

        // Also store in database for long-term audit trail if activity_logs table exists
        if ($this->shouldStoreInDatabase()) {
            $this->storeActivityInDatabase($action, $description, $logData);
        }
    }

    /**
     * Log a security event
     *
     * @param string $event
     * @param string $description
     * @param array $metadata
     * @return void
     */
    protected function logSecurityEvent(
        string $event,
        string $description,
        array $metadata = []
    ): void {
        $this->logActivity(
            'SECURITY_EVENT: ' . $event,
            $description,
            array_merge($metadata, ['security_event' => true]),
            'warning'
        );
    }

    /**
     * Log a financial transaction
     *
     * @param string $transactionType
     * @param float $amount
     * @param array $metadata
     * @return void
     */
    protected function logFinancialTransaction(
        string $transactionType,
        float $amount,
        array $metadata = []
    ): void {
        $this->logActivity(
            'FINANCIAL_TRANSACTION: ' . $transactionType,
            sprintf('Transaction of %.2f executed', $amount),
            array_merge($metadata, [
                'transaction_type' => $transactionType,
                'amount' => $amount,
                'currency' => $metadata['currency'] ?? config('app.currency', 'USD'),
            ]),
            'info'
        );
    }

    /**
     * Log data access for compliance
     *
     * @param string $resourceType
     * @param string $resourceId
     * @param string $action
     * @return void
     */
    protected function logDataAccess(
        string $resourceType,
        string $resourceId,
        string $action = 'view'
    ): void {
        $this->logActivity(
            'DATA_ACCESS: ' . strtoupper($action),
            sprintf('%s accessed %s: %s', ucfirst($action), $resourceType, $resourceId),
            [
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'access_type' => $action,
            ],
            'info'
        );
    }

    /**
     * Log administrative action
     *
     * @param string $action
     * @param string $description
     * @param array $metadata
     * @return void
     */
    protected function logAdminAction(
        string $action,
        string $description,
        array $metadata = []
    ): void {
        $this->logActivity(
            'ADMIN_ACTION: ' . $action,
            $description,
            array_merge($metadata, ['admin_action' => true]),
            'notice'
        );
    }

    /**
     * Check if activity should be stored in database
     *
     * @return bool
     */
    private function shouldStoreInDatabase(): bool
    {
        return config('app.store_activity_logs', false);
    }

    /**
     * Store activity in database
     *
     * @param string $action
     * @param string $description
     * @param array $logData
     * @return void
     */
    private function storeActivityInDatabase(
        string $action,
        string $description,
        array $logData
    ): void {
        try {
            // This would require an activity_logs table
            // DB::table('activity_logs')->insert([
            //     'action' => $action,
            //     'description' => $description,
            //     'user_id' => $logData['user']['id'] ?? null,
            //     'ip_address' => $logData['request']['ip'],
            //     'user_agent' => $logData['request']['user_agent'],
            //     'metadata' => json_encode($logData),
            //     'created_at' => now(),
            // ]);
        } catch (\Exception $e) {
            Log::error('Failed to store activity in database', [
                'error' => $e->getMessage(),
                'action' => $action,
            ]);
        }
    }
}
