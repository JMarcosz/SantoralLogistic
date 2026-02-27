# Documentación Técnica - Módulo de Almacén y Reservas

## 1. Arquitectura de Base de Datos

### 1.1. Tablas Principales

#### `warehouses`

Entidad principal de almacenes.

- `id`, `code`, `name`, `is_active`

#### `locations`

Ubicaciones físicas dentro del almacén.

- `id`, `warehouse_id`, `code`, `type` (Receiving, Storage, Picking)

#### `inventory_items`

Representa el stock almacenado. Agrupa por Cliente, Almacén y SKU.

- `qty`: Cantidad física total.
- **Nota:** No almacena "disponible" explícitamente. `Disponible = qty - sum(reservations)`.

#### `inventory_reservations`

Tabla pivote para bloqueo lógico de inventario.

- `inventory_item_id`: FK a `inventory_items`.
- `shipping_order_id`: FK a `shipping_orders`.
- `qty_reserved`: Cantidad bloqueada.
- **Trazabilidad:** `created_by`, `deleted_by` (User IDs).
- **SoftDeletes:** Los registros eliminados se mantienen para auditoría (`deleted_at`).

#### `warehouse_receipts`

Cabecera de recibos de entrada.

- `status`: Controlado por State Machine (`Draft`, [Received](file:///j:/DWP/2025/SISTEMAS/maedLogisticPlatform/app/Services/WarehouseReceiptStateMachine.php#21-85), `Closed`, `Cancelled`).

#### `warehouse_receipt_lines`

Detalle de artículos a recibir.

- Atributos de trazabilidad: `lot_number`, `serial_number`, `expiration_date`.

---

## 2. Lógica de Negocio (Backend)

### 2.1. Gestión de Entradas (Warehouse Receipts)

#### State Machine ([WarehouseReceiptStateMachine.php](file:///j:/DWP/2025/SISTEMAS/maedLogisticPlatform/app/Services/WarehouseReceiptStateMachine.php))

Controla el ciclo de vida de una recepción.

1.  **Draft:** Estado inicial. Permite edición completa.
2.  **Mark Received:**
    - Transición crítica (método [markReceived](file:///j:/DWP/2025/SISTEMAS/maedLogisticPlatform/app/Services/WarehouseReceiptStateMachine.php#21-85)).
    - **Creación de Inventario:** Itera sobre las líneas y crea registros en `inventory_items`.
    - **Ubicación Inicial:** Los items se crean con `location_id = null`.
    - **Movimiento:** Genera registros en `inventory_movements` con tipo `Generate` (o Receive).
3.  **Closed:** Estado final inmutable.
4.  **Cancelled:** Estado final para anulaciones (solo si no se ha recibido aún).

### 2.2. InventoryReservationService

Servicio core para la gestión de reservas [app/Services/InventoryReservationService.php](file:///j:/DWP/2025/SISTEMAS/maedLogisticPlatform/app/Services/InventoryReservationService.php).

#### Métodos Clave:

- [reserveForShippingOrder(ShippingOrder $so, array $lines)](file:///j:/DWP/2025/SISTEMAS/maedLogisticPlatform/app/Services/InventoryReservationService.php#22-127):

  - **Concurrency:** Usa `lockForUpdate()` en [InventoryItem](file:///j:/DWP/2025/SISTEMAS/maedLogisticPlatform/app/Models/InventoryItem.php#10-155) dentro de una transacción DB para evitar condiciones de carrera (overselling) durante picos de tráfico.
  - **FIFO Logic:** Asigna stock priorizando por `received_at` (o ID por defecto) para rotación de inventario.
  - **Validation:** Check de saldo disponible (`qty - reserved`).

- [findAvailableInventoryForUpdate(...)](file:///j:/DWP/2025/SISTEMAS/maedLogisticPlatform/app/Services/InventoryReservationService.php#206-226):

  - Helper crítico que aplica el row-level lock.

- [releaseReservationsForShippingOrder(ShippingOrder $so)](file:///j:/DWP/2025/SISTEMAS/maedLogisticPlatform/app/Services/InventoryReservationService.php#128-158):
  - Libera el stock (SoftDelete de la reserva).
  - Registra `deleted_by` con el usuario actual.

### 2.3. Optimización de Performance

Se evita el problema N+1 en listados cargando las sumas de reservas:

```php
InventoryItem::withSum('reservations as reserved_qty_sum', 'qty_reserved')
```

Esto permite calcular [availableQuantity](file:///j:/DWP/2025/SISTEMAS/maedLogisticPlatform/app/Models/InventoryItem.php#91-98) en memoria sin queries adicionales por item.

---

## 3. API Endpoints

### `GET /api/inventory/search`

Endpoint para consumo del frontend (autocompletado).

- **Controller:** `InventoryController@searchAvailable`
- **Params:** `query` (string), `customer_id` (int)
- **Response:** JSON con items agrupados por SKU con su disponibilidad real calculada.

---

## 4. Frontend (React/Inertia)

### 4.1. Componentes Clave

- **[InventoryPicker](file:///j:/DWP/2025/SISTEMAS/maedLogisticPlatform/resources/js/Pages/inventory/components/inventory-picker.tsx#37-183)** ([resources/js/Pages/inventory/components/inventory-picker.tsx](file:///j:/DWP/2025/SISTEMAS/maedLogisticPlatform/resources/js/Pages/inventory/components/inventory-picker.tsx)):

  - Combobox avanzado con búsqueda (debounce 300ms).
  - Muestra estado de carga (`isPending` via `useTransition`).
  - Cancelación de requests obsoletos (`AbortController`).

- **[ReserveInventoryDialog](file:///j:/DWP/2025/SISTEMAS/maedLogisticPlatform/resources/js/Pages/shipping-orders/show.tsx#864-1076)** ([resources/js/Pages/shipping-orders/show.tsx](file:///j:/DWP/2025/SISTEMAS/maedLogisticPlatform/resources/js/Pages/shipping-orders/show.tsx)):
  - Modal transaccional.
  - Integra [InventoryPicker](file:///j:/DWP/2025/SISTEMAS/maedLogisticPlatform/resources/js/Pages/inventory/components/inventory-picker.tsx#37-183) para selección segura de items.

---

## 5. Testing

Tests ubicados en [tests/Feature/InventoryReservationServiceTest.php](file:///j:/DWP/2025/SISTEMAS/maedLogisticPlatform/tests/Feature/InventoryReservationServiceTest.php).
Cubren:

- Creación correcta de reservas.
- Cálculo de disponibilidad.
- Auditoría de usuarios.
- SoftDeletes.
- Excepciones por falta de stock.
