# Guía de Contribución – Proyecto Maed Logistic

Esta guía explica cómo trabajamos en el repositorio, cómo tomar tareas del board y qué se espera de cada desarrollador.

## 1. Reglas de oro

- **NUNCA** hacer commit directo a `main` ni a `develop`.
- **SIEMPRE** crear una rama nueva para cada tarea/issue.
- Antes de empezar cualquier cosa, actualiza tu copia local:

````bash
git checkout develop
git pull origin develop

- Un PR debe resolver **una sola tarea/issue**. Evitar PRs gigantes con muchas cosas mezcladas.
- No subir archivos sensibles (`.env`, claves, backups de BD, etc.).

## 2\. Ramas y flujo de trabajo

Usamos un flujo sencillo:

- `main`: rama estable, lista para producción.
- `develop`: rama de integración del sprint.
- `feature/...`: ramas de trabajo de cada desarrollador.

### 2.1. Nomenclatura de ramas

Usa estos prefijos seguidos de una descripción corta en kebab-case (inglés o español):

- `feat/` – nueva funcionalidad
    - Ej: `feat/settings-currencies-crud`, `feat/crm-customers-index`
- `fix/` – corrección de errores
    - Ej: `fix/quotes-total-rounding`, `fix/login-redirect`
- `docs/` – cambios de documentación
    - Ej: `docs/update-readme`, `docs/add-install-steps`

Ejemplo:

```bash
git checkout develop
git pull origin develop
git checkout -b feat/crm-customers-index
````

---

## 3\. Pasos para trabajar una tarea

1.  **Elegir tarea**
    - Ve al Project de GitHub.
    - Toma una tarjeta de la columna **Ready / Por Hacer**.
    - Asígnate la tarjeta y muévela a **In Progress**.

2.  **Crear la rama**

    ```bash
    git checkout develop
    git pull origin develop
    git checkout -b feat/nombre-tarea
    ```

3.  **Programar la solución**
    - Implementa lo descrito en el issue.
    - Haz commits pequeños y frecuentes.

4.  **Formatear y revisar código**

    Backend:

    ```bash
    ./vendor/bin/pint
    php artisan test      # cuando haya tests
    ```

    Frontend:

    ```bash
    npm run format        # Prettier
    # npm test            # cuando haya tests de frontend
    ```

5.  **Subir cambios**

    ```bash
    git push origin feat/nombre-tarea
    ```

6.  **Crear Pull Request (PR)**
    - Base branch: **`develop`**.
    - Título claro, por ejemplo:
        - `feat: CRM customers index page`
        - `fix: corrige error en cálculo de total de quotes`
    - En la descripción del PR:
        - Qué hace el cambio.
        - Issue relacionado (ej: `Closes #12`).
        - Cómo probarlo (rutas, pasos, datos de prueba).

7.  **Solicitar revisión**
    - Asigna el PR al Tech Lead.
    - Mueve la tarjeta del board a **In Review**.

8.  **Después del merge**
    - Cuando el PR se apruebe y se haga merge a `develop`, mueve la tarjeta a **Done**.

---

## 4\. Estándares de código

### 4.1. PHP / Laravel

- **Clases**: PascalCase
    - `QuoteController`, `CreateCustomerRequest`, `ShippingOrderService`
- **Variables y métodos**: camelCase
    - `$customerId`, `$totalAmount`, `calculateTotal()`
- **Migrations**:
    - Nombres descriptivos:
        - `create_customers_table`, `add_status_to_quotes_table`
- **Controladores**:
    - Mantenerlos ligeros.
    - La lógica de negocio debe ir en servicios, actions o modelos.
- **Requests**:
    - Usar Form Requests para validaciones.

### 4.2. JavaScript / React (Inertia)

- **Componentes**: PascalCase
    - `CustomersIndex`, `QuoteForm`, `Layout`
- **Hooks, funciones y variables**: camelCase
    - `useQuoteForm`, `handleSubmit`, `isLoading`
- Evitar lógica muy compleja dentro de los componentes; extraer a helpers/hooks cuando sea necesario.

### 4.3. Commits

Mensajes claros y descriptivos:

- **MAL:**
    - `arreglos`
    - `cambios varios`
- **BIEN:**
    - `feat: agrega CRUD de customers en CRM`
    - `fix: corrige bug en filtro de status de quotes`
    - `docs: actualiza pasos de instalación en README`

---

## 5\. Checklist antes de abrir un PR

### 5.1 Antes de crear un Pull Request, verifica:

- [ ] La rama parte de la última versión de `develop` (`git pull origin develop` antes de empezar).
- [ ] El código compila y la app levanta sin errores.
- [ ] Ejecutaste los formateadores:
    - `./vendor/bin/pint`
    - `npm run format`
- [ ] No se subieron cambios en `.env` ni archivos sensibles.
- [ ] El PR resuelve **una sola** tarea/issue.
- [ ] El PR tiene descripción y pasos para probar los cambios.
-[ ] La rama parte de la última versión de `develop` (`git pull origin develop` antes de empezar). 
-[ ] El código compila y la app levanta sin errores.
-[ ] Ejecutaste los formateadores: - `./vendor/bin/pint` - `npm run format`
-[ ] No se subieron cambios en `.env` ni archivos sensibles. 
-[ ] El PR resuelve **una sola** tarea/issue. 
-[ ] El PR tiene descripción y pasos para probar los cambios.

## 6\. Uso del tablero (Project Board)

Columnas principales:

- **Backlog**
    - Ideas y tareas futuras. No se toman sin que el Tech Lead las priorice.
- **Ready / Por Hacer**
    - Tareas listas, con definición clara. Solo se toman de aquí.
- **In Progress**
    - Tareas que estás trabajando.
    - Regla: si está en _In Progress_ debe tener un responsable asignado.
- **In Review**
    - Tareas que ya tienen PR abierto y están esperando revisión.
- **Done**
    - PR mergeado a `develop` y funcionalidad probada mínimamente.

## 7\. Comunicación y dudas

- Si algo en la tarea no está claro:
    - Comenta directamente en el issue o en el PR, o
    - Escribe por el canal de comunicación del proyecto.
- No esperes al final del día para preguntar: es mejor estar bloqueado 10 minutos que 10 horas.

<!-- end list -->
