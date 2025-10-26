<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\ProductWarehouseStock;

class ProductWarehouseStockObserver
{
    /**
     * Handle the ProductWarehouseStock "created" event.
     */
    public function created(ProductWarehouseStock $stock): void
    {
        $this->updateProductStock($stock->product_id);
    }

    /**
     * Handle the ProductWarehouseStock "updated" event.
     */
    public function updated(ProductWarehouseStock $stock): void
    {
        $this->updateProductStock($stock->product_id);
    }

    /**
     * Handle the ProductWarehouseStock "deleted" event.
     */
    public function deleted(ProductWarehouseStock $stock): void
    {
        $this->updateProductStock($stock->product_id);
    }

    /**
     * Handle the ProductWarehouseStock "restored" event.
     */
    public function restored(ProductWarehouseStock $stock): void
    {
        $this->updateProductStock($stock->product_id);
    }

    /**
     * Update the product's total stock from all warehouses
     */
    protected function updateProductStock(int $productId): void
    {
        $product = Product::find($productId);

        if (!$product) {
            return;
        }

        // Calculate total stock across all warehouses
        $totalStock = ProductWarehouseStock::where('product_id', $productId)
            ->sum('quantity');

        // Update product stock
        $product->current_stock = $totalStock;

        // Update last restocked date if stock increased
        if ($totalStock > 0 && (!$product->last_restocked_at || $product->wasChanged('current_stock'))) {
            $product->last_restocked_at = now();
        }

        $product->saveQuietly(); // Use saveQuietly to avoid infinite loops
    }
}
