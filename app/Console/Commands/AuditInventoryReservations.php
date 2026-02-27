<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Audit command to detect data inconsistencies in inventory reservations.
 * Created for AUD-INVRES-1 audit.
 */
class AuditInventoryReservations extends Command
{
    protected $signature = 'audit:inventory-reservations';
    protected $description = 'Run diagnostic queries to detect inventory reservation inconsistencies';

    public function handle(): int
    {
        $this->info('');
        $this->info('=== AUDITORÍA DE RESERVAS DE INVENTARIO ===');
        $this->info('');

        $hasIssues = false;

        // 1. Reservas activas con qty_reserved <= 0
        $this->info('1) Reservas activas con qty_reserved <= 0:');
        $badQty = DB::table('inventory_reservations')
            ->whereNull('deleted_at')
            ->where('qty_reserved', '<=', 0)
            ->get();

        if ($badQty->isEmpty()) {
            $this->line('   ✓ Ninguna encontrada');
        } else {
            $hasIssues = true;
            $this->error("   ✗ Encontradas: {$badQty->count()}");
            foreach ($badQty as $r) {
                $this->line("     - ID: {$r->id}, qty_reserved: {$r->qty_reserved}");
            }
        }

        // 2. Reservas activas donde qty_reserved > qty del item
        $this->info('');
        $this->info('2) Reservas activas que exceden qty del item:');
        $exceedQty = DB::table('inventory_reservations as ir')
            ->join('inventory_items as ii', 'ir.inventory_item_id', '=', 'ii.id')
            ->whereNull('ir.deleted_at')
            ->whereRaw('ir.qty_reserved > ii.qty')
            ->select('ir.id', 'ir.qty_reserved', 'ii.id as item_id', 'ii.item_code', 'ii.qty as item_qty')
            ->get();

        if ($exceedQty->isEmpty()) {
            $this->line('   ✓ Ninguna encontrada');
        } else {
            $hasIssues = true;
            $this->error("   ✗ Encontradas: {$exceedQty->count()}");
            foreach ($exceedQty as $r) {
                $this->line("     - Reserva ID: {$r->id}, reserved: {$r->qty_reserved}, item {$r->item_code} qty: {$r->item_qty}");
            }
        }

        // 3. Total reservado por item que excede qty (over-reservation)
        $this->info('');
        $this->info('3) Items con total reservado > qty disponible (over-reservation):');
        $overReserved = DB::table('inventory_items as ii')
            ->leftJoin('inventory_reservations as ir', function ($join) {
                $join->on('ir.inventory_item_id', '=', 'ii.id')
                    ->whereNull('ir.deleted_at');
            })
            ->select('ii.id', 'ii.item_code', 'ii.qty', DB::raw('COALESCE(SUM(ir.qty_reserved), 0) as total_reserved'))
            ->groupBy('ii.id', 'ii.item_code', 'ii.qty')
            ->havingRaw('COALESCE(SUM(ir.qty_reserved), 0) > ii.qty')
            ->get();

        if ($overReserved->isEmpty()) {
            $this->line('   ✓ Ninguna encontrada');
        } else {
            $hasIssues = true;
            $this->error("   ✗ Encontradas: {$overReserved->count()}");
            foreach ($overReserved as $r) {
                $this->line("     - Item ID: {$r->id} ({$r->item_code}), qty: {$r->qty}, total_reserved: {$r->total_reserved}");
            }
        }

        // 4. Shipping Orders delivered/closed con reservas activas
        $this->info('');
        $this->info('4) Shipping Orders cerradas/entregadas con reservas activas:');
        $closedWithReservations = DB::table('shipping_orders as so')
            ->join('inventory_reservations as ir', function ($join) {
                $join->on('ir.shipping_order_id', '=', 'so.id')
                    ->whereNull('ir.deleted_at');
            })
            ->whereIn('so.status', ['delivered', 'closed'])
            ->select('so.id', 'so.order_number', 'so.status', DB::raw('COUNT(ir.id) as active_reservations'))
            ->groupBy('so.id', 'so.order_number', 'so.status')
            ->get();

        if ($closedWithReservations->isEmpty()) {
            $this->line('   ✓ Ninguna encontrada');
        } else {
            $hasIssues = true;
            $this->error("   ✗ Encontradas: {$closedWithReservations->count()}");
            foreach ($closedWithReservations as $r) {
                $this->line("     - SO: {$r->order_number} ({$r->status}), reservas activas: {$r->active_reservations}");
            }
        }

        // 5. Reservas huérfanas (sin shipping order)
        $this->info('');
        $this->info('5) Reservas huérfanas (shipping_order eliminada):');
        $orphanedReservations = DB::table('inventory_reservations as ir')
            ->leftJoin('shipping_orders as so', 'ir.shipping_order_id', '=', 'so.id')
            ->whereNull('ir.deleted_at')
            ->whereNull('so.id')
            ->select('ir.id', 'ir.shipping_order_id', 'ir.qty_reserved')
            ->get();

        if ($orphanedReservations->isEmpty()) {
            $this->line('   ✓ Ninguna encontrada');
        } else {
            $hasIssues = true;
            $this->error("   ✗ Encontradas: {$orphanedReservations->count()}");
            foreach ($orphanedReservations as $r) {
                $this->line("     - Reserva ID: {$r->id}, SO ID: {$r->shipping_order_id}, qty: {$r->qty_reserved}");
            }
        }

        // 6. Reservas con inventory_item eliminado
        $this->info('');
        $this->info('6) Reservas con inventory_item inexistente:');
        $noItem = DB::table('inventory_reservations as ir')
            ->leftJoin('inventory_items as ii', 'ir.inventory_item_id', '=', 'ii.id')
            ->whereNull('ir.deleted_at')
            ->whereNull('ii.id')
            ->select('ir.id', 'ir.inventory_item_id', 'ir.qty_reserved')
            ->get();

        if ($noItem->isEmpty()) {
            $this->line('   ✓ Ninguna encontrada');
        } else {
            $hasIssues = true;
            $this->error("   ✗ Encontradas: {$noItem->count()}");
            foreach ($noItem as $r) {
                $this->line("     - Reserva ID: {$r->id}, Item ID: {$r->inventory_item_id}");
            }
        }

        // Summary
        $this->info('');
        $this->info('=== RESUMEN ===');
        if ($hasIssues) {
            $this->error('Se encontraron inconsistencias. Revisar y corregir antes de continuar.');
            return Command::FAILURE;
        } else {
            $this->info('✓ No se encontraron inconsistencias en las reservas de inventario.');
            return Command::SUCCESS;
        }
    }
}
