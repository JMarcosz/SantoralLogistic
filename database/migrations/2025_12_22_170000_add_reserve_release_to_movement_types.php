<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Changes movement_type from enum to varchar to allow flexible movement types.
     * The validation is handled in the MovementType PHP enum instead.
     */
    public function up(): void
    {
        // PostgreSQL: Drop the check constraint and change to varchar
        if (DB::connection()->getDriverName() === 'pgsql') {
            // Drop the check constraint that limits enum values
            DB::statement("ALTER TABLE inventory_movements DROP CONSTRAINT IF EXISTS inventory_movements_movement_type_check");
            // Change to varchar which allows any value (validation in PHP enum)
            DB::statement("ALTER TABLE inventory_movements ALTER COLUMN movement_type TYPE VARCHAR(50) USING movement_type::text");
        }

        // MySQL: Modify the enum column to varchar
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE inventory_movements MODIFY COLUMN movement_type VARCHAR(50) NOT NULL");
        }

        // SQLite: Recreate the table with varchar instead of enum
        // This is necessary because SQLite doesn't allow ALTER COLUMN
        if (DB::connection()->getDriverName() === 'sqlite') {
            // Disable foreign key checks temporarily
            DB::statement('PRAGMA foreign_keys=off');

            // Create new table without the enum constraint
            DB::statement('
                CREATE TABLE inventory_movements_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    inventory_item_id INTEGER NOT NULL,
                    movement_type VARCHAR(50) NOT NULL,
                    qty DECIMAL(14,4) NOT NULL,
                    from_location_id INTEGER,
                    to_location_id INTEGER,
                    reference VARCHAR(100),
                    notes TEXT,
                    user_id INTEGER,
                    created_at DATETIME,
                    updated_at DATETIME,
                    FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
                    FOREIGN KEY (from_location_id) REFERENCES locations(id) ON DELETE SET NULL,
                    FOREIGN KEY (to_location_id) REFERENCES locations(id) ON DELETE SET NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                )
            ');

            // Copy data
            DB::statement('
                INSERT INTO inventory_movements_new 
                SELECT id, inventory_item_id, movement_type, qty, from_location_id, to_location_id, reference, notes, user_id, created_at, updated_at
                FROM inventory_movements
            ');

            // Drop old table
            DB::statement('DROP TABLE inventory_movements');

            // Rename new table
            DB::statement('ALTER TABLE inventory_movements_new RENAME TO inventory_movements');

            // Re-enable foreign keys
            DB::statement('PRAGMA foreign_keys=on');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not easily reversible - the enum values are now more flexible
    }
};
