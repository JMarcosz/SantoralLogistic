# 🚢 Maed Logistic Platform

Plataforma unificada de gestión logística (TMS), almacenamiento (WMS) y relaciones con clientes (CRM) desarrollada para **Maed Logistic Trading**.

Este sistema centraliza la operación de cotizaciones, órdenes de embarque, inventario y facturación, reemplazando procesos manuales y sistemas legados.

## 🎯 Objetivo

Digitalizar y estandarizar el flujo operativo de Maed Logistic Trading, empezando por un MVP que cubre:

- **Settings núcleo** (monedas, modos, tipos de paquete, puertos).
- **CRM** (clientes y contactos).
- **Quotes** (cotizaciones).
- **Shipping Orders** (órdenes de embarque) conectadas a las quotes.

---

## 🛠 Tech Stack

Arquitectura monolítica moderna basada en **Laravel** e **Inertia.js**:

- **Backend:** Laravel 12 (PHP 8.3)
- **Frontend:** React 19 + Inertia.js
- **UI Framework:** TailwindCSS + shadcn/ui
- **Base de Datos:** PostgreSQL 16
- **Colas & Cache:** Redis (Laravel Horizon)
- **Infraestructura Local:** Docker (Laravel Sail) – _opción recomendada_

---

## 📦 Estado del Proyecto (MVP)

- [x] Setup base (Laravel, Inertia, Tailwind, auth)
- [x] Roles y permisos (Spatie)
- [x] Settings núcleo (currencies, transport modes, package types, ports)
- [x] CRM (customers & contacts)
- [x] Quotes (cotizaciones)
- [x] Shipping Orders (órdenes de embarque)
- [ ] Reportes iniciales

---

## 🚀 Instalación

### 1. Requisitos previos

**Opción recomendada (Docker):**

- Docker Desktop instalado y corriendo
- Git

**Opción alternativa (sin Docker):**

- PHP 8.3
- Composer
- Node.js + npm
- PostgreSQL 16
- Redis

### 2. Clonar el repositorio

```bash
git clone https://github.com/arielbritorivera/maedLogisticPlatform.git
cd maedLogisticPlatform
```

---

## 🔹 Opción A: Instalación con Docker (Laravel Sail) – Recomendada

1. Instalar dependencias PHP:

```bash
composer install
```

2. Copiar archivo de entorno y generar key:

```bash
cp .env.example .env
php artisan key:generate
```

3. Levantar contenedores:

```bash
./vendor/bin/sail up -d
```

4. Ejecutar migraciones y seeders dentro de Sail:

```bash
./vendor/bin/sail artisan migrate --seed
```

5. Instalar dependencias frontend y levantar entorno de desarrollo:

```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

6. Acceder a la aplicación en el navegador (por defecto):

- http://localhost

---

## 🔹 Opción B: Instalación sin Docker (entorno local)

1. Instalar dependencias PHP:

```bash
composer install
```

2. Copiar archivo de entorno y generar key:

```bash
cp .env.example .env
php artisan key:generate
```

3. Configurar base de datos PostgreSQL y Redis en el archivo `.env`.

4. Ejecutar migraciones y seeders:

```bash
php artisan migrate --seed
```

5. Instalar dependencias frontend:

```bash
npm install
npm run dev
```

6. Levantar el servidor de desarrollo:

```bash
php artisan serve
```

---

## 🧭 Estructura del proyecto (resumen)

- `app/Models` → Modelos de Eloquent
- `app/Http/Controllers` → Controladores HTTP
- `app/Services` → Lógica de negocio (servicios)
- `database/migrations` → Migraciones de base de datos
- `database/seeders` → Seeders de datos
- `resources/js` → Frontend (React + Inertia)
- `resources/views` → Plantillas base para Inertia

---

## 🧪 Testing

- **Backend (PHPUnit):**

```bash
# Con Docker (Sail)
./vendor/bin/sail artisan test

# Sin Docker
php artisan test
```

- **Frontend:**  
  Por definir stack de tests; de momento, las pruebas son manuales.

---

## 🧩 Flujo de trabajo con Git

- No se hacen commits directos a `main` ni `develop`.
- Cada tarea del board → nueva rama:
    - `feat/...` para nuevas funcionalidades
    - `fix/...` para correcciones
    - `docs/...` para documentación
- Los Pull Requests van siempre contra `develop`.

Consultar la [Guía de Contribución](./CONTRIBUTING.md) para más detalles.

---

## 🤝 Contribución

1. Elegir una tarea en el Project de GitHub (columna **Ready**).
2. Crear rama `feat/...` desde `develop`.
3. Programar, formatear y ejecutar tests.
4. Crear PR → `develop` y solicitar revisión.
5. Al aprobarse, se mergea y se mueve la tarea a **Done**.

---

## 📞 Contacto

- Tech Lead: Ariel Brito
- Stakeholder de negocio: Reynaldo (Maed Logistic Trading)
