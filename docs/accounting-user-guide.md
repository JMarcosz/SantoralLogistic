# Guía de Usuario - Módulo de Contabilidad

Esta guía explica cómo utilizar el módulo de contabilidad de MAED Logistic Platform.

---

## Acceder al Módulo

Navegue a **Contabilidad** en el menú principal. Encontrará las siguientes secciones:

- 📊 **Plan de Cuentas** - Gestión del catálogo contable
- 📝 **Libro Diario** - Asientos contables
- 📖 **Libro Mayor** - Movimientos por cuenta
- 📅 **Períodos** - Control de períodos contables
- 📈 **Reportes** - Estados financieros
- 🏦 **Conciliación Bancaria** - Conciliación de cuentas
- 🔍 **Auditoría** - Registro de cambios

---

## Plan de Cuentas

### Crear una Cuenta

1. Vaya a **Contabilidad > Plan de Cuentas**
2. Clic en **+ Nueva Cuenta**
3. Complete los campos:
    - **Código**: Número único (ej: 1.1.01)
    - **Nombre**: Nombre descriptivo
    - **Tipo**: Activo, Pasivo, Patrimonio, Ingreso o Gasto
    - **Cuenta Padre**: Si es subcuenta
    - **Es Contabilizable**: Marcar si permite movimientos directos

### Estructura Jerárquica

Las cuentas se organizan en árbol. Ejemplo:

```
1. ACTIVOS
   1.1 Activos Corrientes
       1.1.01 Caja General
       1.1.02 Bancos
   1.2 Activos No Corrientes
       1.2.01 Mobiliario
```

---

## Asientos Contables

### Crear un Asiento

1. Vaya a **Contabilidad > Libro Diario**
2. Clic en **+ Nuevo Asiento**
3. Complete:
    - **Fecha**: Fecha del asiento
    - **Descripción**: Concepto de la operación
4. Agregue líneas con débitos y créditos
5. Verifique que **Total Débitos = Total Créditos**
6. Guarde como **Borrador**

### Contabilizar un Asiento

1. Abra el asiento en estado **Borrador**
2. Verifique que esté balanceado
3. Clic en **Contabilizar**

> ⚠️ Una vez contabilizado, el asiento **no puede modificarse**.

### Reversar un Asiento

Si necesita corregir un asiento contabilizado:

1. Abra el asiento en estado **Contabilizado**
2. Clic en **Reversar**
3. Ingrese el motivo de la reversión
4. Se creará un nuevo asiento con valores invertidos

---

## Períodos Contables

### Cerrar un Período

1. Vaya a **Contabilidad > Períodos**
2. Seleccione el período a cerrar
3. Clic en **Cerrar Período**

> Una vez cerrado, no se pueden contabilizar asientos en ese período.

### Reabrir un Período

Si necesita hacer ajustes:

1. Vaya a **Contabilidad > Períodos**
2. Seleccione el período cerrado
3. Clic en **Reabrir Período**

---

## Reportes Financieros

### Balance General

Muestra la situación financiera a una fecha específica.

1. Vaya a **Contabilidad > Reportes > Balance General**
2. Seleccione la fecha ("Al día...")
3. Visualice: Activos = Pasivos + Patrimonio
4. Exporte a CSV si lo necesita

### Estado de Resultados

Muestra ingresos y gastos de un período.

1. Vaya a **Contabilidad > Reportes > Estado de Resultados**
2. Seleccione el mes/período
3. Opcionalmente marque **YTD** para acumulado del año
4. Visualice: Ingresos - Gastos = Utilidad/Pérdida

### Libro Mayor

Ver movimientos de una cuenta específica.

1. Vaya a **Contabilidad > Libro Mayor**
2. Seleccione una cuenta
3. Defina rango de fechas
4. Visualice movimientos con saldo corrido

---

## Conciliación Bancaria

### Crear Estado de Cuenta

1. Vaya a **Contabilidad > Conciliación Bancaria**
2. Clic en **+ Nuevo Estado de Cuenta**
3. Seleccione la cuenta bancaria
4. Ingrese período y saldos inicial/final

### Agregar Transacciones

- **Manualmente**: Agregue líneas una por una
- **Importar CSV**: Cargue archivo con transacciones

### Conciliar

1. Para cada línea del estado de cuenta:
    - Busque el movimiento correspondiente en el libro mayor
    - Clic en **Conciliar**
2. Cuando todas las líneas estén conciliadas, marque como **Completado**

### Ver Partidas Pendientes

Vaya a **Partidas Pendientes** para ver movimientos del libro mayor sin conciliar.

---

## Auditoría

### Ver Historial de Cambios

1. Vaya a **Contabilidad > Auditoría**
2. Use los filtros para buscar:
    - Por módulo (Asientos, Cuentas, etc.)
    - Por acción (Creado, Actualizado, etc.)
    - Por usuario
    - Por fecha

### Detalle de un Cambio

Haga clic en un registro para ver:

- Valores anteriores vs nuevos
- Usuario que realizó el cambio
- Fecha y hora exacta
- Historial completo de la entidad

### Exportar

Use el botón **Exportar CSV** para descargar los logs.

---

## Preguntas Frecuentes

**¿Por qué no puedo editar un asiento?**
Los asientos contabilizados son inmutables. Use la función de reversión para corregir.

**¿Por qué no puedo contabilizar en cierta fecha?**
El período contable puede estar cerrado. Verifique en Períodos.

**¿Cómo elimino una cuenta?**
Solo puede eliminar cuentas que no tengan subcuentas ni movimientos.

**¿Dónde veo quién hizo cambios?**
En la sección de Auditoría encontrará el historial completo.

---

_Para soporte técnico, contacte al administrador del sistema._
