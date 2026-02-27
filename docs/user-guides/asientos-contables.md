# Módulo de Asientos Contables (Libro Diario)

## Descripción General

El módulo de **Asientos Contables** permite registrar todas las transacciones contables de la empresa mediante el sistema de partida doble. Cada asiento debe estar balanceado (total débitos = total créditos) antes de ser contabilizado.

---

## Acceso al Módulo

1. En el menú lateral, expanda **Contabilidad**
2. Seleccione **Asientos Contables**

---

## Funcionalidades

### 📋 Lista de Asientos

La pantalla principal muestra todos los asientos contables con:

| Campo              | Descripción                              |
| ------------------ | ---------------------------------------- |
| **Número**         | Identificador único (ej: JE-2025-000001) |
| **Fecha**          | Fecha del asiento                        |
| **Descripción**    | Concepto del asiento                     |
| **Estado**         | Borrador, Contabilizado o Reversado      |
| **Débito/Crédito** | Totales en moneda base                   |

**Filtros disponibles:**

- Búsqueda por número o descripción
- Filtro por estado
- Rango de fechas

---

### ➕ Crear Nuevo Asiento

1. Clic en **Nuevo Asiento**
2. Complete los campos:
    - **Fecha**: Fecha de la transacción
    - **Descripción**: Concepto general del asiento

3. Agregue líneas (mínimo 2):
    - Seleccione la **cuenta contable**
    - Ingrese monto en **Débito** O **Crédito** (no ambos)
    - Opcionalmente: descripción de línea, moneda diferente

4. Verifique que el indicador muestre **"Balanceado"** (verde)
5. Clic en **Guardar como Borrador**

> ⚠️ **Importante**: Cada línea debe tener solo débito O crédito, nunca ambos.

---

### ✏️ Editar Asiento

Solo los asientos en estado **Borrador** pueden editarse.

1. Abra el asiento desde la lista
2. Clic en **Editar**
3. Modifique los campos necesarios
4. Guarde los cambios

---

### ✅ Contabilizar Asiento

Cambia el estado de **Borrador** a **Contabilizado**.

**Requisitos:**

- El asiento debe estar balanceado
- El período contable debe estar abierto

**Pasos:**

1. Abra el asiento
2. Clic en **Contabilizar**
3. Confirme la acción

> 📌 Una vez contabilizado, el asiento NO puede editarse ni eliminarse.

---

### ↩️ Reversar Asiento

Crea un nuevo asiento que anula el original (invierte débitos y créditos).

**Requisitos:**

- El asiento debe estar **Contabilizado**
- El período contable debe estar abierto

**Pasos:**

1. Abra el asiento contabilizado
2. Clic en **Reversar**
3. Confirme la acción

**Resultado:**

- El asiento original cambia a estado **Reversado**
- Se crea un nuevo asiento con montos invertidos

---

### 🗑️ Eliminar Asiento

Solo los asientos en estado **Borrador** pueden eliminarse.

1. Abra el asiento
2. Clic en **Eliminar**
3. Confirme la acción

---

## Estados del Asiento

| Estado            | Color       | Acciones Permitidas            |
| ----------------- | ----------- | ------------------------------ |
| **Borrador**      | 🟡 Amarillo | Editar, Eliminar, Contabilizar |
| **Contabilizado** | 🟢 Verde    | Reversar                       |
| **Reversado**     | 🔴 Rojo     | Solo consulta                  |

---

## Reglas de Negocio

1. **Partida Doble**: Total débitos debe igualar total créditos
2. **Mínimo 2 líneas**: Todo asiento requiere al menos una línea de débito y una de crédito
3. **Una dirección por línea**: Cada línea tiene débito O crédito, nunca ambos
4. **Período abierto**: Solo se puede contabilizar/reversar si el período está abierto
5. **Inmutabilidad**: Asientos contabilizados no pueden modificarse, solo reversarse

---

## Multi-Moneda

El módulo soporta transacciones en múltiples monedas:

- Cada línea puede tener una moneda diferente
- El sistema convierte automáticamente a la moneda base usando la tasa de cambio
- Los totales se muestran en moneda base

---

## Preguntas Frecuentes

**¿Por qué no puedo guardar el asiento?**

- Verifique que el asiento esté balanceado (diferencia = 0)
- Asegúrese de tener al menos una línea de débito y una de crédito
- Confirme que todas las cuentas estén seleccionadas

**¿Por qué no puedo contabilizar?**

- El período contable podría estar cerrado
- Verifique que el asiento esté balanceado

**¿Cómo corrijo un asiento ya contabilizado?**

- Use la función **Reversar** para anular el asiento
- Luego cree un nuevo asiento con los valores correctos

---

## Permisos Requeridos

| Permiso                   | Descripción                      |
| ------------------------- | -------------------------------- |
| `journal_entries.view`    | Ver lista y detalle de asientos  |
| `journal_entries.create`  | Crear nuevos asientos            |
| `journal_entries.edit`    | Editar asientos en borrador      |
| `journal_entries.delete`  | Eliminar asientos en borrador    |
| `journal_entries.post`    | Contabilizar asientos            |
| `journal_entries.reverse` | Reversar asientos contabilizados |
