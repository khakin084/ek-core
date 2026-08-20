<?php

namespace Modules\Approvals\Services;

use App\Services\Http\BaseMicroserviceClient;
use App\Services\Http\TokenType;

/**
 * ek-core BFF — thin client to ek-auth's internal approval endpoints.
 * Every method here hits /api/v1/internal/* (EnsureClientToken-guarded), so all
 * requests go out with the service token, not the user's.
 */
class ApprovalService extends BaseMicroserviceClient
{
    protected string $defaultService = 'ek-auth';
    public const FILTERS = ['requester', 'request_type', 'request_status', 'date_from', 'date_to'];

    /**
     * Upsert a flow. Returns [status, decodedBody] so the controller can relay 422/409 back to
     * the form instead of swallowing them.
     *
     * @return array{0:int,1:array}
     */
    public function saveFlow(array $payload): ?array
    {
        $res = $this->serviceRequest('/api/v1/internal/flows')
            ->post($this->url('/api/v1/internal/flows'), $payload);

        if ($res->successful()) {
            return $this->parseJson($res);
        }

        $this->logFailure('saveFlow', $res, ['payload' => $payload]);

        return null;
    }

    /** Types dropdown for the form (service token, tenant-scoped). */
    public function approvalTypes(?string $tenantId): array
    {
        return $this->listResource(
            '/api/v1/internal/approval-types/dropdown',
            'approvalTypes',
            ['tenant' => $tenantId],
            as: TokenType::Service,
        );
    }

    public function fetchFlow(string $id): ?array
    {
        return $this->fetchResource(
            "/api/v1/internal/flows/{$id}",
            'fetchFlow',
            as: TokenType::Service,
        );
    }

    public function deleteFlow(string $id): bool
    {
        return $this->deleteResource(
            "/api/v1/internal/flows/{$id}",
            'deleteFlow',
            as: TokenType::Service,
        );
    }

    public function getApprovalFlowDataTable(array $dt = []): array
    {
        $query = buildDtQuery($dt);

        return $this->listResourceForDataTable(
            '/api/v1/approval-flow/list',
            'getApprovalFlowDataTable',
            $query,
            as: TokenType::User,
        );
    }
 
    public function getApprovalsDataTable(array $dt = []): array
    {
        return $this->listResourceForDataTable(
            '/api/v1/approvals/requests',
            'getApprovalsDataTable',
            dtForward($dt, self::FILTERS),
            as: TokenType::User,
            service: 'ek-approvals',
        );
    }
}