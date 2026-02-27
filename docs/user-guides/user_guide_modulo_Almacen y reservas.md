# Manual de Usuario - Módulo de Almacén (WMS)

Este documento describe el funcionamiento del Módulo de Gestión de Almacén (WMS) de la plataforma Maed Logistic.

## 1. Configuración del Almacén

Antes de operar, es necesario configurar la estructura física de los almacenes.

### 1.1. Almacenes (Warehouses)
**Ruta:** Configuración > Almacenes
Aquí se registran los centros de distribución físicos.
- **Campos:** Código, Nombre, Dirección, Activo.

### 1.2. Ubicaciones (Locations)
**Ruta:** Configuración > Ubicaciones
Define las posiciones específicas dentro de un almacén (pasillos, estantes, bins).
- **Tipos de Ubicación:**
    - *Receiving:* Zona de descarga temporal.
    - *Storage:* Almacenamiento regular.
    - *Picking:* Zona de preparación.
    - *Shipping:* Zona de carga.

---

## 2. Recepción de Mercancía (Inbound)

### 2.1. Warehouse Receipts (WR)
**Ruta:** Operaciones > Recibos de Almacén
Documenta la entrada de mercancía al almacén.
- **Flujo:**
    1. Crear Recibo (seleccionar Cliente y Almacén).
    2. Agregar ítems (SKU, Cantidad, Ubicación de recepción).
    3. Finalizar Recibo: Esto incrementa el inventario disponible.

---

## 3. Gestión de Inventario

### 3.1. Visualización de Stock
**Ruta:** Inventario > Existencias
Muestra el listado de productos almacenados.
- **Filtros:** Por Almacén, Cliente, SKU.
- **Columnas:** Cantidad Física, **Cantidad Reservada**, **Cantidad Disponible**.
    - *Física:* Total en estanterías.
    - *Reservada:* Comprometida para Shipping Orders pero aún en almacén.
    - *Disponible:* Física - Reservada.

### 3.2. Movimientos de Inventario
Permite reorganizar la mercancía.
- **Reubicar (Relocate):** Mover pallet/items de una ubicación A a una ubicación B.
- **Ajustes (Adjust):** Corregir diferencias de inventario (pérdidas, daños, hallazgos). Requiere justificación.

### 3.3. Conteos Cíclicos (Cycle Counts)
**Ruta:** Inventario > Conteos Cíclicos
Herramienta para auditar el inventario físico contra el sistema sin detener la operación completa.

---

## 4. Salida de Mercancía (Outbound)

### 4.1. Reservas de Inventario
**Ruta:** Shipping Orders > Detalle > Reservar Inventario
Vincula el stock existente a una Orden de Envío para asegurar que no se venda a otro cliente.
- **Nueva Funcionalidad:**
    - Botón **"Reservar Inventario"** en la Shipping Order.
    - **Inventory Picker:** Buscador inteligente que muestra disponibilidad en tiempo real por SKU.
    - **Bloqueo:** El sistema impide reservar más de lo disponible.
    - **Liberación:** Si la orden se cancela, se puede "Liberar Reservas" para devolver el stock a disponible.

### 4.2. Warehouse Orders (Picking)
Instrucciones de trabajo para el personal de almacén para recolectar la mercadería reservada.
- Se genera a partir de una Shipping Order reservada.
- Indica al operador a qué ubicación ir y cuánto recoger.

---

## 5. Reportes y Dashboards
**Ruta:** Almacén > Dashboard
Vista general de la ocupación del almacén, movimientos recientes y alertas de stock bajo.
