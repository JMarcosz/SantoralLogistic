# Inventory Architecture Refactor Walkthrough

I have successfully re-architected the inventory system to support "Stock Lots" (no aggregation), ensure strict traceability, and fix the receiving location issue.

## Changes Implemented

### 1. Renamed SKU to Item Code
- **Database**: Renamed `sku` column to `item_code` in `inventory_items` and `warehouse_receipt_lines` tables.
- **Codebase**: Updated [InventoryItem](file:///j:/DWP/2025/SISTEMAS/maedLogisticPlatform/app/Models/InventoryItem.php#10-164), [WarehouseReceiptLine](file:///j:/DWP/2025/SISTEMAS/maedLogisticPlatform/app/Models/WarehouseReceiptLine.php#9-61) models, and all related Services/Controllers to use `item_code`.
- **API**: Listing/Search endpoints now support `item_code` filters (backward compatibility for `sku` removed to avoid confusion).

### 2. Stock Lots Architecture (No Aggregation)
- **Concept**: Items are no longer merged when moved to the same location. Each receipt line generates a distinct [InventoryItem](file:///j:/DWP/2025/SISTEMAS/maedLogisticPlatform/app/Models/InventoryItem.php#10-164) record that persists throughout its lifecycle.
- **Implementation**:
    - Added `warehouse_receipt_line_id` to `inventory_items` to permanently link inventory to its source receipt line.
    - Updated `InventoryMovementService::relocate` to **always create a new item** (or move the existing one) rather than merging into an existing item. This preserves the unique identity and `received_at` timestamp of every lot.

### 3. Fixed Receiving Location
- **Issue**: Previously, received items had `location_id = null`.
- **Fix**: Updated `WarehouseReceiptStateMachine::markReceived` to assign items to a "RECEIVING" location (type: dock) instead of null.
- **Migration**: A migration ensures a 'RECEIVING' location exists for every warehouse.

## Verification Results

### Automated Tests
Run `php artisan test tests/Feature/InventoryArchitectureTest.php`

- [x] **Receiving**: Verified that receiving creates distinct inventory items linked to the receipt line and assigned to the 'RECEIVING' location.
- [x] **No Aggregation**: Verified that moving two identical items (same code, same lot) to the same rack results in **two separate inventory records**, preventing data loss (traceability).
