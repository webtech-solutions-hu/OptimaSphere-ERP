<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UnitController extends Controller
{
    /**
     * Display a listing of units.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Unit::with(['baseUnit', 'derivedUnits']);

        // Filters
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('code', 'like', "%{$request->search}%")
                    ->orWhere('symbol', 'like', "%{$request->search}%");
            });
        }

        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        if ($request->boolean('paginate', true)) {
            $perPage = $request->get('per_page', 15);
            $units = $query->paginate($perPage);
        } else {
            $units = $query->get();
        }

        return response()->json($units);
    }

    /**
     * Store a newly created unit.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|unique:units,code|max:255',
            'name' => 'required|string|max:255',
            'symbol' => 'nullable|string|max:255',
            'type' => 'required|in:weight,volume,length,area,quantity,time,other',
            'base_unit_id' => 'nullable|exists:units,id',
            'conversion_factor' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $unit = Unit::create($request->all());
        $unit->load(['baseUnit']);

        return response()->json([
            'message' => 'Unit created successfully',
            'data' => $unit,
        ], 201);
    }

    /**
     * Display the specified unit.
     */
    public function show(Unit $unit): JsonResponse
    {
        $unit->load(['baseUnit', 'derivedUnits', 'products']);

        return response()->json([
            'data' => $unit,
        ]);
    }

    /**
     * Update the specified unit.
     */
    public function update(Request $request, Unit $unit): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'sometimes|string|unique:units,code,' . $unit->id . '|max:255',
            'name' => 'sometimes|string|max:255',
            'symbol' => 'nullable|string|max:255',
            'type' => 'sometimes|in:weight,volume,length,area,quantity,time,other',
            'base_unit_id' => 'nullable|exists:units,id',
            'conversion_factor' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $unit->update($request->all());
        $unit->load(['baseUnit', 'derivedUnits']);

        return response()->json([
            'message' => 'Unit updated successfully',
            'data' => $unit,
        ]);
    }

    /**
     * Remove the specified unit.
     */
    public function destroy(Unit $unit): JsonResponse
    {
        // Check if unit has products
        if ($unit->products()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete unit with associated products',
            ], 400);
        }

        $unit->delete();

        return response()->json([
            'message' => 'Unit deleted successfully',
        ]);
    }
}
