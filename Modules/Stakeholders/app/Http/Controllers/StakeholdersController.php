<?php

namespace Modules\Stakeholders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DropdownCacheService;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Modules\Stakeholders\Http\Requests\StakeholderRequest;
use Modules\Stakeholders\Services\StakeholdersService;

class StakeholdersController extends Controller
{
    protected DropdownCacheService $dropdownCacheService;
    protected StakeholdersService $stakeholdersService;

    public function __construct(
        DropdownCacheService $dropdownCacheService,
        StakeholdersService $stakeholdersService
    ) {
        $this->dropdownCacheService = $dropdownCacheService;
        $this->stakeholdersService = $stakeholdersService;
    }

    public function index(): View
    {
        abort_unless(userCan('stakeholders', 'read'), 403, 'You are not allowed to perform this action.');

        return view('stakeholders::index', [
            'title' => 'Stakeholders',
            'auditData' => [
                'module' => 'Stakeholders',
                'entity' => 'Stakeholder',
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('stakeholders::create', [
            'industries' => [],
            'title' => 'Create Stakeholder',
        ]);
    }

    public function store(StakeholderRequest $request)
    {
        abort_unless(userCan('stakeholders', 'read_write'), 403, 'You are not allowed to perform this action.');
        infoLogger('info.log', json_encode($request->all()));
        $id = $request->input('id'); // present on edit, null on create
        $isEdit = filled($id);

        try {
            // The request has already validated and transformed the data
            $payload = $request->toPayload();
            $payload['context_snapshot'] = json_encode([
                'created_by' => actingUser(),
            ]);

            $result = $isEdit
                ? $this->stakeholdersService->updateResource('/api/v1/stakeholders/' . $id, $payload, 'updateStakeholder')
                : $this->stakeholdersService->storeResource('/api/v1/stakeholders/store', $payload, 'storeStakeholder');

            if ($result !== null) {
                return $result;
            }

            return apiFail('Record could not be saved', 500, $payload, $id);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            errorLogger('error.log', $e->getMessage() . json_encode([
                'trace' => $e->getTraceAsString(),
                'input' => $request->except(['password', 'passconf', 'password_confirmation']),
            ]));

            return apiFail('An unexpected error occurred.', 500, [], $id);
        }
    }
    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $stakeholder = $this->stakeholdersService->fetchResource('/api/v1/stakeholders/get/' . $id, 'getStakeholder');
        return view('stakeholders::show', [
            'tenant' => authTenant(),
            'title' => strtoupper($stakeholder['name']),
            'stakeholder' => $stakeholder
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $stakeholder = $this->stakeholdersService->fetchResource('/api/v1/stakeholders/get/' . $id, 'getStakeholder');
        return view('stakeholders::create', [
            'stakeholder' => $stakeholder,
            'industries' => [],
            'title' => 'Edit ' . ucwords($stakeholder['name'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
    }

    public function dataTable(Request $request)
    {
        $dt = dtParams($request, StakeholdersService::FILTERS);

        $result = $this->stakeholdersService->getStakeholdersDataTable($dt);

        if (!userCan('stakeholders', 'read')) {
            return dtEmpty($dt, 'You do not have access to stakeholder data.');
        }

        return dtRelay($dt, $result);
    }
}
