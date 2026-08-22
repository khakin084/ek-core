<?php

namespace Modules\Stakeholders\Services;

use App\Services\Http\BaseMicroserviceClient;
use App\Services\Http\TokenType;

/**
 * ek-core BFF — thin client to ek-stakeholders's internal stakeholder endpoints.
 */
class StakeholdersService extends BaseMicroserviceClient
{
    protected string $defaultService = 'ek-stakeholders';
    public const FILTERS = [];

    public function getStakeholdersDataTable(array $dt = []): array
    {
        return $this->listResourceForDataTable(
            '/api/v1/stakeholders/data',
            'getStakeholdersDataTable',
            dtForward($dt, self::FILTERS),
            as: TokenType::User
        );
    }
}