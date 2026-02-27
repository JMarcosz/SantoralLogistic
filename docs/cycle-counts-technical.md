# Módulo de Conteos Cíclicos - Documentación Técnica

## Arquitectura General

El módulo de Conteos Cíclicos sigue la arquitectura estándar de Laravel con Inertia.js + React:

```
┌─────────────────┐     ┌────────────────┐     ┌─────────────────┐
│   Frontend      │────▶│   Controller   │────▶│    Service      │
│   (React/TSX)   │◀────│   (Inertia)    │◀────│  (Business)     │
└─────────────────┘     └────────────────┘     └─────────────────┘
                              │                        │
                              ▼                        ▼
                        ┌────────────┐          ┌────────────┐
                        │   Policy   │          │   Model    │
                        │  (Auth)    │          │  (Eloquent)│
                        └────────────┘          └────────────┘
```

---

## Estructura de Archivos

```
app/
├── Enums/
│   └── CycleCountStatus.php          # Estados del conteo
├── Http/Controllers/
│   └── CycleCountController.php      # Controlador principal
├── Models/
│   ├── CycleCount.php                # Modelo principal
│   └── CycleCountLine.php            # Líneas del conteo
├── Policies/
│   └── CycleCountPolicy.php          # Autorización
└── Services/
    └── CycleCountService.php         # Lógica de negocio

resources/js/
└── pages/cycleCounts/
    ├── index.tsx                     # Lista de conteos
    ├── create.tsx                    # Formulario de creación
    └── show.tsx                      # Detalle y edición

routes/
└── web.php                           # Definición de rutas
```

---

## Modelos de Datos

### CycleCount

| Campo          | Tipo     | Descripción              |
| -------------- | -------- | ------------------------ |
| `id`           | bigint   | Identificador único      |
| `warehouse_id` | bigint   | Almacén donde se realiza |
| `status`       | enum     | Estado actual del conteo |
| `reference`    | string   | Referencia opcional      |
| `scheduled_at` | datetime | Fecha programada         |
| `completed_at` | datetime | Fecha de completado      |
| `notes`        | text     | Notas adicionales        |
| `created_by`   | bigint   | Usuario que lo creó      |
| `created_at`   | datetime | Fecha de creación        |
| `updated_at`   | datetime | Última actualización     |

### CycleCountLine

| Campo               | Tipo    | Descripción                 |
| ------------------- | ------- | --------------------------- |
| `id`                | bigint  | Identificador único         |
| `cycle_count_id`    | bigint  | Conteo padre                |
| `inventory_item_id` | bigint  | Item de inventario          |
| `sku`               | string  | SKU del producto            |
| `description`       | string  | Descripción                 |
| `location`          | string  | Ubicación                   |
| `expected_qty`      | decimal | Cantidad esperada (sistema) |
| `counted_qty`       | decimal | Cantidad contada (real)     |
| `difference_qty`    | decimal | Diferencia calculada        |

---

## CycleCountStatus Enum

```php
enum CycleCountStatus: string
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
```

### Transiciones Válidas

```
Draft ────────▶ InProgress
  │                │
  │                ├────▶ Completed
  │                │
  └────────────────┴────▶ Cancelled
```

| Desde             | Hacia      | Método       |
| ----------------- | ---------- | ------------ |
| Draft             | InProgress | `start()`    |
| InProgress        | Completed  | `complete()` |
| Draft, InProgress | Cancelled  | `cancel()`   |

---

## CycleCountService

### Métodos Principales

#### `create(Warehouse $warehouse, array $data, array $filters = []): CycleCount`

Crea un nuevo conteo cíclico con sus líneas.

```php
$service->create(
    warehouse: $warehouse,
    data: [
        'reference' => 'Conteo Mensual',
        'scheduled_at' => '2024-12-20',
        'notes' => 'Conteo de fin de año',
    ],
    filters: [
        'customer_id' => 1,     // Opcional
        'sku' => 'ELEC',        // Opcional (contiene)
        'location_id' => 5,     // Opcional
    ]
);
```

**Flujo interno:**

1. Crea el registro `CycleCount` en estado Draft
2. Consulta `InventoryItem` aplicando filtros
3. Crea una `CycleCountLine` por cada item
4. Usa transacción para garantizar atomicidad

---

#### `start(CycleCount $cycleCount): CycleCount`

Inicia un conteo, cambiando su estado a InProgress.

```php
$service->start($cycleCount);
```

**Validaciones:**

- Estado actual debe ser Draft
- Usuario debe tener permiso `cycle_counts.update`

---

#### `updateLine(CycleCount $cycleCount, int $lineId, float $countedQty): CycleCountLine`

Registra la cantidad contada para una línea.

```php
$service->updateLine($cycleCount, $lineId, 95.5);
```

**Cálculos automáticos:**

- `difference_qty = counted_qty - expected_qty`

---

#### `complete(CycleCount $cycleCount): CycleCount`

Completa el conteo y genera ajustes de inventario.

```php
$service->complete($cycleCount);
```

**Flujo interno:**

1. Valida que el estado sea InProgress
2. Para cada línea con diferencia:
    - Crea movimiento de ajuste (`adjustment`)
    - Actualiza `qty` del `InventoryItem`
3. Marca `completed_at`
4. Cambia estado a Completed
5. Registra en log de actividad

---

#### `cancel(CycleCount $cycleCount, ?string $reason = null): CycleCount`

Cancela un conteo sin generar ajustes.

```php
$service->cancel($cycleCount, 'Error en selección de productos');
```

---

## Rutas API

```php
// Lista de conteos
GET  /cycle-counts                    → index()

// Formulario de creación
GET  /cycle-counts/create             → create()

// Crear conteo
POST /cycle-counts                    → store()

// Ver detalle
GET  /cycle-counts/{cycleCount}       → show()

// Iniciar conteo
POST /cycle-counts/{cycleCount}/start → start()

// Actualizar línea
PATCH /cycle-counts/{cycleCount}/lines/{line} → updateLine()

// Completar conteo
POST /cycle-counts/{cycleCount}/complete → complete()

// Cancelar conteo
POST /cycle-counts/{cycleCount}/cancel   → cancel()
```

---

## Políticas de Autorización

### CycleCountPolicy

```php
class CycleCountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cycle_counts.view_any');
    }

    public function view(User $user, CycleCount $cycleCount): bool
    {
        return $user->can('cycle_counts.view');
    }

    public function create(User $user): bool
    {
        return $user->can('cycle_counts.create');
    }

    public function update(User $user, CycleCount $cycleCount): bool
    {
        // No se puede editar en estados terminales
        if ($cycleCount->isTerminal()) {
            return false;
        }
        return $user->can('cycle_counts.update');
    }

    public function complete(User $user, CycleCount $cycleCount): bool
    {
        if (!$cycleCount->canComplete()) {
            return false;
        }
        return $user->can('cycle_counts.complete');
    }

    public function cancel(User $user, CycleCount $cycleCount): bool
    {
        if (!$cycleCount->canCancel()) {
            return false;
        }
        return $user->can('cycle_counts.cancel');
    }
}
```

---

## Frontend (React/TypeScript)

### Páginas

#### `index.tsx`

- Lista paginada de conteos
- Filtros por estado y almacén
- Navegación a create/show

#### `create.tsx`

- Wizard de 3 pasos:
    1. Selección de almacén (cards visuales)
    2. Filtros opcionales (cliente, SKU)
    3. Confirmación y datos adicionales

#### `show.tsx`

- Header con gradiente y estado
- 4 KPIs: Progreso, Diferencias, Pendientes, Estado
- Tabla de líneas con:
    - Búsqueda por SKU/descripción/ubicación
    - Filtros rápidos (todos/pendientes/contados/diferencias)
- Dialog de edición de línea con preview de diferencia
- Dialog de confirmación de cancelación
- Notificaciones toast para feedback

### Rutas Wayfinder

```typescript
import cycleCountRoutes from '@/routes/cycle-counts';

// Uso
cycleCountRoutes.index().url; // /cycle-counts
cycleCountRoutes.create().url; // /cycle-counts/create
cycleCountRoutes.store().url; // /cycle-counts (POST)
cycleCountRoutes.show(id).url; // /cycle-counts/{id}
cycleCountRoutes.start(id).url; // /cycle-counts/{id}/start
cycleCountRoutes.linesUpdate(id, lineId).url; // /cycle-counts/{id}/lines/{lineId}
cycleCountRoutes.complete(id).url; // /cycle-counts/{id}/complete
cycleCountRoutes.cancel(id).url; // /cycle-counts/{id}/cancel
```

---

## Permisos

Agregar al `PermissionSeeder`:

```php
// Cycle Counts
'cycle_counts.view_any',
'cycle_counts.view',
'cycle_counts.create',
'cycle_counts.update',
'cycle_counts.complete',
'cycle_counts.cancel',
```

---

## Seeder de Pruebas

```bash
php artisan db:seed --class=WarehouseInventorySeeder
```

Crea:

- 3 Almacenes con 66 ubicaciones cada uno
- 4 Recepciones de mercancía
- 20 Items de inventario (5 por cliente)

---

## Consideraciones de Performance

1. **Lazy Loading en líneas**: Las líneas se cargan con el conteo, considerar paginación para conteos grandes
2. **Transacciones**: Todas las operaciones de escritura usan transacciones DB
3. **Índices recomendados**:
    - `cycle_counts(warehouse_id, status)`
    - `cycle_count_lines(cycle_count_id)`
    - `cycle_count_lines(sku)`

---

## Logs y Auditoría

El servicio registra eventos importantes:

```php
Log::info('Cycle count started', [
    'cycle_count_id' => $cycleCount->id,
    'user_id' => auth()->id(),
]);

Log::info('Inventory adjustment from cycle count', [
    'cycle_count_id' => $cycleCount->id,
    'sku' => $line->sku,
    'adjustment' => $difference,
]);
```

---

## Extensibilidad

### Agregar nuevos filtros

1. Modificar `CycleCountController@store` para validar el nuevo filtro
2. Modificar `CycleCountService@create` para aplicar el filtro en la query
3. Actualizar `create.tsx` con el campo del formulario

### Agregar notificaciones

Considerar eventos Laravel:

- `CycleCountStarted`
- `CycleCountCompleted`
- `CycleCountCancelled`

---

_Documentación técnica actualizada: Diciembre 2024_
