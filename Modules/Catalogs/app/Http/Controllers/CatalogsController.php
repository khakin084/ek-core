<?php

namespace Modules\Catalogs\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DropdownCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Catalogs\Services\CatalogService;
use Yajra\DataTables\Facades\DataTables;

class CatalogsController extends Controller
{
    const MODULE = "Item Master";
    protected DropdownCacheService $dropdownCacheService;
    protected CatalogService $catalogService;

    public function __construct(
        DropdownCacheService $dropdownCacheService,
        CatalogService $catalogService
    ) {
        $this->dropdownCacheService = $dropdownCacheService;
        $this->catalogService = $catalogService;
    }

    public function index()
    {
        // $data['accounts_options'] = $this->dropdownCacheService->get('accounts', ['ACCOUNT']);
        $data['variety_options'] = $this->dropdownCacheService->get('items', ['VARIETY']);
        $data['units_options'] = $this->dropdownCacheService->get('units');
        $data['item_groups'] = $this->dropdownCacheService->get('item_groups');
        return view('catalogs::index', $data);
    }

    /*** Item ***/
    public function createItem($encodedId = null)
    {
        $id = $encodedId ? decodeUrlx($encodedId) : null;
        // Fetch item data from catalog service if editing
        $item = null;
        if ($id) {
            $item = $this->catalogService->getItem($id);
        }

        $module = $this::MODULE;
        $varietyOptions = $this->dropdownCacheService->get('items', ['VARIETY']);
        return view('catalogs::items.form', compact('module', 'varietyOptions', 'item'));
    }

    public function createCompositeItem()
    {
        $module = getModule('ITEM MASTER');
        $items_options = $this->dropdownCacheService->get('items', ['CONSUMABLE', 'NON-CONSUMABLE', 'COMPOSITE']);
        $services_options = $this->dropdownCacheService->get('items', ['SERVICE']);
        $units_options = $this->dropdownCacheService->get('units');
        $tax_code_options = $this->dropdownCacheService->get('tax_codes');
        return view('catalogs::items.composite_item.form', compact('module', 'items_options', 'services_options', 'units_options', 'tax_code_options'));
    }

    public function itemsDataTable(Request $request)
    {
        $dt = dtParams($request, ['item_group_id', 'item_variety_id']);

        $result = $this->catalogService->getItemsDataTable($dt);

        // Inject action column if needed (same as before)
        if (is_array($result) && isset($result['data']) && is_array($result['data'])) {
            $result['data'] = array_map(function ($row) {
                $id = $row['id'] ?? null;
                $row['action'] = $id
                    ? '<div class="table-data-feature pull-left">
                        <button onclick="edit(\'' . route('item-create', $id) . '\',\'DTTBL-BTN\',\'items_container\')" class="item" type="button" title="Edit"><i class="zmdi zmdi-edit"></i></button>
                        <button onclick="destroy(\'' . route('item-delete', $id) . '\',\'DTTBL-BTN\',\'items_container\')" class="item" type="button" title="Delete"><i class="zmdi zmdi-delete"></i></button>
                   </div>'
                    : '';
                return $row;
            }, $result['data']);
        }

        return response()->json($result ?? [
            'draw' => $dt['draw'],
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => 'Failed to load item groups',
        ], $result ? 200 : 502);
    }

    public function storeItem(Request $request)
    {
        $id = $request->input('id');
        $payload = [
            'name' => $request->input('name'),
            'item_group_id' => $request->input('item_group_id'),
            'variety_id' => $request->input('variety_id'),
            'unit_id' => $request->input('unit_id'),
            'descriptions' => $request->input('descriptions'),
        ];

        if ($id) {
            $result = $this->catalogService->updateItem($id, $payload);
        } else {
            $result = $this->catalogService->createItem($payload);
        }

        if ($result) {
            return response()->json([
                'state' => 'Done',
                'title' => 'Successful',
                'msg' => 'Record Successfully Saved',
                'newname' => $result['name'] ?? null,
                'newval' => $result['id'] ?? null
            ]);
        }

        return response()->json([
            'state' => 'Fail',
            'title' => 'Fail',
            'msg' => 'Record could not be saved'
        ], 500);
    }

    public function storeCompositeItem()
    {

    }

    public function deleteItem($id)
    {
        $id = decodeUrlx($id);
        $result = $this->catalogService->deleteItem($id);

        if ($result) {
            return response()->json([
                'state' => 'Done',
                'title' => 'Successful',
                'msg' => 'Record Successfully Deleted'
            ]);
        }

        return response()->json([
            'state' => 'Fail',
            'title' => 'Fail',
            'msg' => 'Record could not be deleted'
        ], 500);
    }

    public function getItemComponents(string $encodedId): JsonResponse
    {
        $id = decodeUrlx($encodedId);

        // Fetch dropdown options using the cache service
        $itemsOptions = $this->dropdownCacheService->get('items', ['CONSUMABLE', 'NON-CONSUMABLE', 'COMPOSITE', 'FIXED ASSET']);
        $servicesOptions = $this->dropdownCacheService->get('items', ['SERVICE']);
        $unitsOptions = $this->dropdownCacheService->get('units');
        $taxCodeOptions = $this->dropdownCacheService->get('tax_codes');

        // Initialize arrays and item variable
        $itemComponents = [];
        $costComponents = [];
        $item = null;

        // If a valid ID is provided, fetch the item and its components
        if ($id) {
            $item = $this->catalogService->getItem($id);
            if (!$item) {
                abort(404, 'Item not found');
            }
            $itemComponents = $this->catalogService->getItemComponents($id, ['ITEM']);
            $costComponents = $this->catalogService->getItemComponents($id, ['COST']);
        }

        // Render the components table view with all necessary data
        $componentsTable = view('item-master.items.components-table', compact(
            'itemsOptions',
            'unitsOptions',
            'taxCodeOptions',
            'item',
            'itemComponents',
            'costComponents',
            'servicesOptions'
        ))->render();

        // Return JSON response containing the rendered HTML
        return response()->json([
            'components_table' => $componentsTable,
        ]);
    }

    public function getItemTrackRecords($id)
    {
        // Decode the ID (assuming base64urlDecode is a helper function)
        $id = decodeUrlx($id);

        // Get entity dropdown options for STAKEHOLDER type
        $entities_options = $this->dropdownCacheService->get('entities', ['STAKEHOLDER']);

        $item = null;
        $inventory_detail = null;

        if ($id > 0) {
            $item = $this->catalogService->getItem($id);
            if (!$item) {
                abort(404, 'Item not found');
            }
            $inventory_detail = $this->catalogService->getInventoryDetail($id);
        }

        // Render the view
        $ret_data['track_stock_table'] = view(
            'item_master.items.track_stock_table',
            compact('entities_options', 'item', 'inventory_detail')
        )->render();

        return response()->json($ret_data);
    }

    public function getVariations(Request $request, $variety_id, $item_id = null)
    {
        $variety_id = decodeUrlx($variety_id);
        $item_id = !is_null($item_id) ? decodeUrlx($item_id) : null;

        $items_options = $this->dropdownCacheService->get('items');
        $units_options = $this->dropdownCacheService->get('units');

        $result1 = $this->catalogService->getItem($variety_id);
        $result2 = !is_null($item_id) ? $this->catalogService->getItem($item_id) : [];
        $variety = $result1['item'];
        $item = !is_null($item_id) ? $result2['item'] : [];

        // Get all item-variety-particular associations for this variety
        $iivps = $this->catalogService->getIivps($variety_id);

        // Build thead and tbody HTML
        $thead = '<thead><tr>';
        $tbody = '<tbody id="item_item_variety_particulars_tbody">';

        foreach ($iivps as $index => $iivp) {
            $ivp = $iivp['particular'];
            $width = $iivp['has_value_with_unit'] ? 70 : 100;
            $border_radius = ['$ivp->has_value_with_unit'] ? 'border-top-right-radius: 0 !important; border-bottom-right-radius: 0 !important;' : '';

            $thead .= '<th><div class="some-handle"></div>' . e($ivp['name']) . '</th>';

            $tbody .= '<td>
            <div class="input-group select-group">
                <select name="item_variety_particular_values[]" id="item_variety_particular_values_' . $index . '" its_width="' . $width . '%" style="max-width: ' . $width . '% !important; ' . $border_radius . '" class="form-control-sm form-control selectXs">';

            $valueOptions = collect($ivp['values'] ?? [])
                ->pluck('name', 'id')
                ->toArray();
            foreach ($valueOptions as $valId => $valName) {
                $selected = ($item && in_array($valId, $item['variety_particular_value_ids'])) ? 'selected' : '';
                $tbody .= '<option ' . $selected . ' value="' . $valId . '">' . e($valName) . '</option>';
            }

            $tbody .= '</select>
                <input name="item_variety_particular_ids[]" type="hidden" value="' . $ivp['id'] . '">';

            if ($iivp['has_value_with_unit']) {
                $tbody .= '<input name="unit_id" type="text" class="form-control input-group-addon" style="max-width:30%;" value="" readonly>';
            }

            $tbody .= '</div>
        </td>';
        }

        $thead .= '</tr></thead>';
        $tbody .= '</tbody>';

        // Render the view
        $html = view('catalogs::items.item_item_form', compact('items_options', 'units_options', 'variety', 'item', 'thead', 'tbody'))->render();

        return response()->json(['variation_table' => $html]);
    }

    public function rearrangeVariations(Request $request)
    {
        $itemVarietyId = decodeUrlx($request->input('item_variety'));
        $variationIds = $request->input('item_variety_particular_ids', []);

        $payload = [];
        $result = $this->catalogService->rearrangeVariations($payload);
        if ($result) {
            return response()->json([
                'state' => 'Done',
                'title' => 'Successful',
                'msg' => 'Record Successfully Saved',
                'newname' => $result['name'] ?? null,
                'newval' => $result['id'] ?? null
            ]);
        }

        return response()->json([
            'state' => 'Fail',
            'title' => 'Fail',
            'msg' => 'Record could not be saved'
        ], 500);
    }

    /*** Item Group ***/
    public function createItemGroup(string|null $id = null)
    {
        $id = $id ? decodeUrlx($id) : null;
        $data['item_group'] = null;
        if ($id) {
            $data['item_group'] = $this->catalogService->getItemGroup($id);
        }
        return view('catalogs::item_groups.form', $data);
    }

    public function itemGroupsDataTable(Request $request)
    {
        $dt = dtParams($request);

        $result = $this->catalogService->getItemGroupsList($dt);

        // Inject action column if needed (same as before)
        if (is_array($result) && isset($result['data']) && is_array($result['data'])) {
            $result['data'] = array_map(function ($row) {
                $id = $row['id'] ?? null;
                $row['action'] = $id
                    ? '<div class="table-data-feature pull-left">
                        <button onclick="edit(\'' . route('item-group-create', $id) . '\',\'DTTBL-BTN\',\'item_groups_container\')" class="item" type="button" title="Edit"><i class="zmdi zmdi-edit"></i></button>
                        <button onclick="destroy(\'' . route('item-group-delete', $id) . '\',\'DTTBL-BTN\',\'item_groups_container\')" class="item" type="button" title="Delete"><i class="zmdi zmdi-delete"></i></button>
                   </div>'
                    : '';
                return $row;
            }, $result['data']);
        }

        return response()->json($result ?? [
            'draw' => $dt['draw'],
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => 'Failed to load item groups',
        ], $result ? 200 : 502);
    }

    public function storeItemGroup(Request $request)
    {
        $id = null;
        try {
            $payload = $request->validate([
                'name' => 'required|string|max:255',
                'code' => [
                    'required',
                    'string',
                    'max:50'
                ],
                'descriptions' => 'nullable|string|max:500',
                'id'
            ]);

            $result = $this->catalogService->storeItemGroup($payload);

            if ($result !== null) {
                return $result;
            }

            $id = $payload['id'] ?? null;
            return apiFail('Record could not be saved', 500, $payload, $id);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            errorLogger('error.log', $e->getMessage() . json_encode([
                'trace' => $e->getTraceAsString(),
                'input' => $request->all(),
            ]));

            return apiFail('An unexpected error occurred.', 500, $payload, $id);
        }
    }

    public function deleteItemGroup($id)
    {
        $id = decodeUrlx($id);
        $result = $this->catalogService->deleteItemGroup($id);

        if ($result) {
            return response()->json([
                'state' => 'Done',
                'title' => 'Successful',
                'msg' => 'Record Successfully Deleted'
            ]);
        }

        return response()->json([
            'state' => 'Fail',
            'title' => 'Fail',
            'msg' => 'Record could not be deleted'
        ], 500);
    }

    /*** Item Variety ***/
    public function createVariety(string|null $id = null)
    {
        $id = $id ? decodeUrlx($id) : null;
        $vpsOptions = $this->dropdownCacheService->get('item_variety_particulars');
        $currenciesOptions = [];
        $unitsOptions = $this->dropdownCacheService->get('units');
        $itemGroups = $this->dropdownCacheService->get('item_groups');

        $itemVariety = null;
        if ($id) {
            $result = $this->catalogService->getItem($id);
            $itemVariety = $result['item'];

            $vp_ids = array_column($itemVariety['variety_particulars'], 'id');
            $itemVariety['variety_particulars'] = $vp_ids;
        }

        // Return the view with all data
        return view('catalogs::varieties.form', compact(
            'vpsOptions',
            'currenciesOptions',
            'unitsOptions',
            'itemGroups',
            'itemVariety'
        ));
    }

    public function varietyDataTable(Request $request)
    {
        $dt = dtParams($request, ['item_group_id']);

        $result = $this->catalogService->getVarietiesList($dt);

        // Inject action column if needed (same as before)
        if (is_array($result) && isset($result['data']) && is_array($result['data'])) {
            $result['data'] = array_map(function ($row) {
                $id = $row['id'] ?? null;
                $row['action'] = $id
                    ? '<div class="table-data-feature pull-left">
                        <a href="' . route('variety-create', [encodeUrlx($id)]) . '" class="item" type="button" data-placement="top" title="Edit Variety"><i class="zmdi zmdi-edit"></i></a>
                        <button onclick="destroy(\'' . route('variety-delete', $id) . '\',\'DTTBL-BTN\',\'varieties_container\')" class="item" type="button" title="Delete"><i class="zmdi zmdi-delete"></i></button>
                   </div>'
                    : '';
                return $row;
            }, $result['data']);
        }

        return response()->json($result ?? [
            'draw' => $dt['draw'],
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => 'Failed to load item groups',
        ], $result ? 200 : 502);
    }

    public function storeVariety(Request $request)
    {
        // dd($request->header());
        $id = $request->input('id');
        $id = null;
        try {
            $validated = $request->validate([
                'id' => 'nullable|integer|max:1000',
                'name' => 'required|string|max:255',
                'item_group_id' => 'required|integer|max:255',
                'item_uom' => 'required|integer|max:255',
                'variety_particulars' => 'required|array|max:255',
                'notes' => 'nullable|string|max:1000',
            ]);

            $result = $this->catalogService->createVariety($validated);

            if ($result !== null) {
                return $result;
            }

            $id = $validated['id'] ?? null;
            return apiFail('Record could not be saved', 500, $validated, $id);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            errorLogger('error.log', $e->getMessage() . json_encode([
                'trace' => $e->getTraceAsString(),
                'input' => $request->all(),
            ]));

            return apiFail('An unexpected error occurred.', 500, $validated, $id);
        }
    }

    public function deleteVariety($id)
    {
        $id = decodeUrlx($id);
        $result = $this->catalogService->deleteVariety($id);

        if ($result) {
            return response()->json([
                'state' => 'Done',
                'title' => 'Successful',
                'msg' => 'Record Successfully Deleted'
            ]);
        }

        return response()->json([
            'state' => 'Fail',
            'title' => 'Fail',
            'msg' => 'Record could not be deleted'
        ], 500);
    }

    public function retrieveUnitNLastPrice($item_id, $warehouse_id, $price_type): JsonResponse
    {
        return response()->json($this->catalogService->retrieveUnitNLastPrice($item_id, $warehouse_id, $price_type));
    }

    /*** Item Variety Particular ***/
    public function createVarietyParticular($id = null)
    {
        $id = !is_null($id) ? decodeUrlx($id) : null;
        // $currenciesOptions = $this->dropdownCacheService->get('currencies');
        $vpsOptions = $this->dropdownCacheService->get('currencies');
        $unitsOptions = $this->dropdownCacheService->get('item_variety_particulars');
        $itemVarietyParticular = null;
        if ($id) {
            $itemVarietyParticular = $this->catalogService->getItemVarietyParticular($id);
        }

        return view('catalogs::variety_particulars.form', compact('itemVarietyParticular', 'unitsOptions', 'vpsOptions'));
    }

    public function storeVarietyParticular(Request $request)
    {
        $id = null;
        try {
            $validated = $request->validate([
                'id' => 'nullable|integer|max:1000',
                'name' => 'required|string|max:255',
                'descriptions' => 'nullable|string|max:1000',
            ]);

            $result = $this->catalogService->createVarietyParticular($validated);

            if ($result !== null) {
                return $result;
            }

            $id = $validated['id'] ?? null;
            return apiFail('Record could not be saved', 500, $validated, $id);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            errorLogger('error.log', $e->getMessage() . json_encode([
                'trace' => $e->getTraceAsString(),
                'input' => $request->all(),
            ]));

            return apiFail('An unexpected error occurred.', 500, $validated, $id);
        }
    }


    /*** Units Of Measure ***/
    public function createUnit($id = null)
    {
        $id = !is_null($id) ? decodeUrlx($id) : null;
        $unit = null;
        if ($id > 0) {
            $unit = $this->catalogService->getUnit($id);
        }
        return view('catalogs::units.form', compact('unit'));
    }

    public function storeUnit(Request $request)
    {
        $id = null;
        try {
            $validated = $request->validate([
                'id' => 'nullable|integer|max:1000',
                'name' => 'required|string|max:255',
                'symbol' => 'required|string|max:255',
                'descriptions' => 'nullable|string|max:1000',
            ]);

            $result = $this->catalogService->createUnit($validated);

            if ($result !== null) {
                return $result;
            }

            $id = $validated['id'] ?? null;
            return apiFail('Record could not be saved', 500, $validated, $id);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            errorLogger('error.log', $e->getMessage() . json_encode([
                'trace' => $e->getTraceAsString(),
                'input' => $request->all(),
            ]));

            return apiFail('An unexpected error occurred.', 500, $validated, $id);
        }
    }


}
