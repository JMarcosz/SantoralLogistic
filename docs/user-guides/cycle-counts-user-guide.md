# Módulo de Conteos Cíclicos - Guía de Usuario

## ¿Qué es un Conteo Cíclico?

Un conteo cíclico es un proceso de verificación del inventario físico que permite comparar las cantidades registradas en el sistema con las existencias reales en el almacén. Este proceso ayuda a:

- Detectar diferencias entre inventario teórico y físico
- Identificar faltantes o excedentes de mercancía
- Mantener la precisión del inventario
- Generar ajustes automáticos cuando se completa el conteo

---

## Acceso al Módulo

**Menú:** Almacén → Conteos Cíclicos  
**URL:** `/cycle-counts`  
**Permisos requeridos:** `cycle_counts.view_any`

---

## Estados de un Conteo

| Estado          | Descripción                                                                |
| --------------- | -------------------------------------------------------------------------- |
| **Borrador**    | Conteo creado pero no iniciado. Se pueden revisar las líneas.              |
| **En Progreso** | Conteo activo. Se pueden registrar las cantidades contadas.                |
| **Completado**  | Conteo finalizado. Los ajustes de inventario se generaron automáticamente. |
| **Cancelado**   | Conteo anulado. No se realizaron ajustes.                                  |

---

## Flujo de Trabajo

```
Crear Conteo → Iniciar → Registrar Cantidades → Completar
     ↓                                              ↓
  Cancelar ←────────────────────────────────── Cancelar
```

---

## Crear un Nuevo Conteo

### Paso 1: Seleccionar Almacén

1. Ir a **Almacén → Conteos Cíclicos**
2. Clic en **Nuevo Conteo**
3. Seleccionar el almacén donde se realizará el conteo

### Paso 2: Aplicar Filtros (Opcional)

Puede filtrar qué productos incluir en el conteo:

| Filtro      | Descripción                                                |
| ----------- | ---------------------------------------------------------- |
| **Cliente** | Solo contar productos de un cliente específico             |
| **SKU**     | Solo contar productos cuyo SKU contenga el texto ingresado |

> **Nota:** Si no aplica ningún filtro, se incluirán TODOS los productos con stock en el almacén seleccionado.

### Paso 3: Confirmar

1. Opcionalmente agregar una **Referencia** (ej: "Conteo Mensual Diciembre")
2. Opcionalmente programar una **Fecha**
3. Agregar **Notas** si es necesario
4. Clic en **Crear Conteo**

---

## Realizar el Conteo

### Iniciar el Conteo

1. Abrir el conteo desde la lista
2. Clic en **Iniciar Conteo**
3. El estado cambiará a "En Progreso"

### Registrar Cantidades

1. En la tabla de líneas, haga clic en cualquier producto
2. Se abrirá un diálogo mostrando la cantidad esperada
3. Ingrese la **cantidad contada**
4. Clic en **Guardar**

La diferencia se calculará automáticamente:

- **Verde (+)**: Hay más unidades de las esperadas (sobrante)
- **Rojo (-)**: Hay menos unidades de las esperadas (faltante)
- **Sin color**: La cantidad coincide exactamente

### Usar los Filtros de Líneas

En la parte superior de la tabla puede filtrar las líneas:

| Filtro          | Muestra                     |
| --------------- | --------------------------- |
| **Todos**       | Todas las líneas            |
| **Pendientes**  | Solo líneas sin contar      |
| **Contados**    | Solo líneas ya contadas     |
| **Diferencias** | Solo líneas con diferencias |

También puede buscar por SKU, descripción o ubicación usando el campo de búsqueda.

---

## Completar el Conteo

1. Cuando todas las líneas estén contadas (o las necesarias)
2. Clic en **Completar**
3. Confirmar la acción

> **Importante:** Al completar el conteo, el sistema generará automáticamente ajustes de inventario para corregir las diferencias encontradas.

---

## Cancelar un Conteo

Si necesita cancelar un conteo:

1. Clic en **Cancelar**
2. Opcionalmente ingresar una razón
3. Confirmar

> **Nota:** Un conteo cancelado no genera ajustes de inventario.

---

## Indicadores del Conteo

En la página de detalle verá cuatro tarjetas informativas:

| Indicador       | Descripción                          |
| --------------- | ------------------------------------ |
| **Progreso**    | Cantidad de líneas contadas vs total |
| **Diferencias** | Número de líneas con diferencias     |
| **Pendientes**  | Líneas que aún no se han contado     |
| **Estado**      | Estado actual del conteo             |

---

## Preguntas Frecuentes

### ¿Puedo editar una línea ya contada?

Sí, mientras el conteo esté "En Progreso" puede hacer clic en cualquier línea y cambiar la cantidad contada.

### ¿Qué pasa si no cuento todas las líneas?

Puede completar el conteo aunque no todas las líneas estén contadas. Solo se ajustarán las líneas que tengan diferencias.

### ¿Puedo volver a un conteo completado?

No. Una vez completado, el conteo no puede modificarse. Si necesita un nuevo conteo, debe crear uno nuevo.

### ¿Dónde veo los ajustes generados?

Los ajustes de inventario se registran en el sistema y pueden verse en el módulo de movimientos de inventario.

---

## Permisos Necesarios

| Permiso                 | Acción                        |
| ----------------------- | ----------------------------- |
| `cycle_counts.view_any` | Ver lista de conteos          |
| `cycle_counts.view`     | Ver detalle de un conteo      |
| `cycle_counts.create`   | Crear nuevos conteos          |
| `cycle_counts.update`   | Registrar cantidades contadas |
| `cycle_counts.complete` | Completar un conteo           |
| `cycle_counts.cancel`   | Cancelar un conteo            |

---

_Documentación actualizada: Diciembre 2024_
