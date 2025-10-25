<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::with(['salesRep', 'priceList', 'category']);

        // Filters
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('region')) {
            $query->where('region', $request->region);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('company_name', 'like', "%{$request->search}%")
                    ->orWhere('first_name', 'like', "%{$request->search}%")
                    ->orWhere('last_name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        $customers = $query->paginate($perPage);

        return response()->json($customers);
    }

    /**
     * Store a newly created customer.
     */
    public function store(Request $request): JsonResponse
    {
        $rules = [
            'type' => 'required|in:b2b,b2c',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'nullable|string',
            'payment_terms' => 'nullable|integer|min:0',
            'credit_limit' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
        ];

        // Type-specific validation
        if ($request->type === 'b2b') {
            $rules['company_name'] = 'required|string|max:255';
            $rules['tax_id'] = 'nullable|string|unique:customers,tax_id';
        } else {
            $rules['first_name'] = 'required|string|max:255';
            $rules['last_name'] = 'required|string|max:255';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $customer = Customer::create($request->all());
        $customer->load(['salesRep', 'priceList', 'category']);

        return response()->json([
            'message' => 'Customer created successfully',
            'data' => $customer,
        ], 201);
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer): JsonResponse
    {
        $customer->load(['salesRep', 'priceList', 'category']);

        return response()->json([
            'data' => $customer,
        ]);
    }

    /**
     * Update the specified customer.
     */
    public function update(Request $request, Customer $customer): JsonResponse
    {
        $rules = [
            'email' => 'sometimes|email|unique:customers,email,' . $customer->id,
            'phone' => 'nullable|string',
            'payment_terms' => 'nullable|integer|min:0',
            'credit_limit' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
        ];

        if ($request->type === 'b2b') {
            $rules['tax_id'] = 'nullable|string|unique:customers,tax_id,' . $customer->id;
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $customer->update($request->all());
        $customer->load(['salesRep', 'priceList', 'category']);

        return response()->json([
            'message' => 'Customer updated successfully',
            'data' => $customer,
        ]);
    }

    /**
     * Remove the specified customer.
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return response()->json([
            'message' => 'Customer deleted successfully',
        ]);
    }

    /**
     * Import customers from file.
     */
    public function import(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Import functionality - to be implemented with file processing',
        ], 501);
    }

    /**
     * Export customers.
     */
    public function export(Request $request): JsonResponse
    {
        $query = Customer::with(['salesRep', 'priceList', 'category']);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $customers = $query->get();

        return response()->json([
            'message' => 'Customers export',
            'data' => $customers,
            'count' => $customers->count(),
        ]);
    }
}
