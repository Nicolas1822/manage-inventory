---
type: plan
title: Plan — Inventario compartido por negocio
description: HOW para aislar los datos por negocio (sesión) y compartirlos entre sus miembros.
tags: [sdd, plan, multi-negocio]
timestamp: 2026-10-30T13:00:00Z
topic: sdd
slug: negocio-compartido
status: planned (autopiloto)
---

# Plan — Inventario compartido por negocio

> Spec: [negocio-compartido](../specs/negocio-compartido.md) · Constitución v1.0.0
> Rama: `feat_negocio_compartido` · BD aislada de la rama: `manage_inventory_negocio`

## 0. Global constraints (literales; toda tarea los cumple)

- Columna de pertenencia: **`id_sesion`**, `foreignId('id_sesion')->constrained('sesion')->restrictOnDelete()`,
  **NOT NULL**, con índice. Tablas: `producto`, `inventario`, `factura`, `venta`, `venta_detalle`,
  `clientes`, `factura_electronica`.
- `id_usuario` **se conserva** en todas las tablas que ya lo tienen y pasa a significar **autor**.
  Nunca vuelve a usarse como filtro de propiedad.
- Marca de superadministrador: `users.es_superadmin` boolean, default `false`. **No** entra en
  `$fillable` y ningún formulario la expone (spec CA-16).
- Un usuario, un negocio: índice único en `sesion_usuario.id_user`.
- Migraciones nuevas en `database/migrations/alter/`, con prefijo `2026_10_30_`. Esa carpeta se
  registra con `loadMigrationsFrom` (hoy ninguna subcarpeta se carga). `triggers/` **no** se registra.
- Regla del filtro por negocio (`NegocioScope`):
  - sin usuario autenticado → **sin filtro** (API n8n y consola: comportamiento de hoy);
  - usuario con negocio **activo** → `where <tabla>.id_sesion = <id>`;
  - usuario sin negocio, con negocio inactivo, o superadmin sin negocio → **resultado vacío**.
- Crear un registro de un modelo con negocio y usuario autenticado sin negocio activo → excepción
  de dominio `SinNegocioException`. Nunca se guarda un registro sin `id_sesion`.
- Tests: `DatabaseTransactions` siempre. Prohibido `RefreshDatabase`, `DatabaseMigrations` y
  `migrate:fresh` dentro de tests (constitución 7).
- Comandos: dentro del contenedor, con el worktree como carpeta de trabajo:
  `docker exec -w /var/www/html/.worktrees/feat_negocio_compartido inventory_app <cmd>`.
- Estilo: Pint (4 espacios) en todo archivo tocado; commits en español, sin emoji ni trailer de IA.

## 1. Context & constraints

- Spec §Goals y CA-1…CA-17. Lo que más pesa: CA-3/CA-4 (aislamiento total) y CA-14 (suite verde).
- Hoy: el filtro `id_usuario = auth()->id()` está repetido en ~30 sitios (Resources, widgets,
  Dashboard, `ReporteVentasService`, `CreateVenta`; los observers solo escriben el autor). `producto` y `venta` no tienen dueño
  propio. La API n8n no filtra por usuario (`index`) o lo hace de forma opcional (`mas-vendidos`).
- Las tablas `sesion` y `sesion_usuario` ya existen y no se usan.
- Constitución 9 (capas), 10 (convenciones de BD y carpeta `alter/`), 7 (tests), 16 (API autenticada).
- Datos actuales de prueba: la BD se reinicia (spec, Non-goals). En la rama se usa una BD aparte para
  no romper la BD de `dev`, que usa otra sesión.

## 2. Architecture

```text
          ┌───────────────── petición web (Filament) ─────────────────┐
          │                                                          │
 Usuario ─┤  NegocioActual (servicio, uno por petición)               │
          │    id(): ?int · tieneNegocio(): bool · esSuperadmin()    │
          │             │                                            │
          │             ▼                                            │
          │  Trait PerteneceANegocio  (en 7 modelos)                 │
          │    ├─ NegocioScope: filtra por id_sesion                 │
          │    └─ creating: rellena id_sesion o lanza excepción      │
          │             │                                            │
          │   Resources / Widgets / Dashboard / Services            │
          │   (consultan sin filtro manual; el scope lo pone)        │
          │                                                          │
          │  Admin (solo superadmin):                                │
          │    SesionResource «Negocios» + MiembrosRelationManager    │
          │    UserResource «Usuarios»                               │
          │    MembresiaNegocio (servicio): asignar / retirar         │
          └──────────────────────────────────────────────────────────┘
 n8n ──── API (n8n.auth, sin usuario) → scope inactivo → igual que hoy
```

**La decisión que más importa: filtro automático (global scope) en vez de filtros manuales.**

- *Opción A — global scope en el modelo* (elegida). Cada consulta de un modelo con negocio sale
  filtrada por defecto, incluida la de `relationship()` en los Select, la búsqueda del registro por
  URL (da 404 sola, CA-4), los widgets y los servicios. Un sitio olvidado queda **seguro por
  defecto**. Coste: un comportamiento implícito que hay que conocer, y ojo con `DB::table()`, que
  salta el scope (hoy no hay ninguno en `app/`).
- *Opción B — tenancy nativa de Filament* (`->tenant(Sesion::class)`). Descartada: solo filtra
  Resources (no widgets, servicios ni Dashboard), cambia todas las URL a `/inventory/{negocio}/…`,
  está pensada para varios negocios por usuario (Non-goal) y deja fuera al superadmin sin negocio.
- *Opción C — reemplazar los 30 `where` a mano*. Descartada: repite justo el patrón cuyo olvido es
  el riesgo principal (R1).

Componentes:

| Componente | Responsabilidad única | Capa (const. 9) |
|---|---|---|
| `App\Services\NegocioActual` | Decir cuál es el negocio activo del usuario autenticado | Service |
| `App\Models\Scopes\NegocioScope` | Aplicar la regla del filtro (§0) | Model scope |
| `App\Models\Concerns\PerteneceANegocio` | Registrar el scope, rellenar `id_sesion` al crear, relación `negocio()` | Model trait |
| `App\Exceptions\SinNegocioException` | Error de dominio al crear sin negocio | Exception |
| `App\Services\MembresiaNegocio` | Asignar y retirar miembros; impedir dos negocios por usuario | Service |
| `SesionResource` + `MiembrosRelationManager` | CRUD de negocios y miembros (superadmin) | Filament |
| `UserResource` | Alta y edición de usuarios, sin marca de superadmin (superadmin) | Filament |
| `Concerns\RequiereNegocio` (trait de Resource) | `canCreate()` solo con negocio activo | Filament trait |
| Aviso «sin negocio» | Render hook del panel con el mensaje de CA-9 | Filament view |
| `SuperAdminSeeder` | Crear el primer superadmin desde `.env` | Seeder |

## 3. Interfaces & contracts

```text
NegocioActual::id(): ?int
  - null si no hay usuario, si no tiene negocio o si su negocio está inactivo
  - memoiza por petición; refrescar(): void borra la memoria (tests de CA-12)
NegocioActual::hayUsuario(): bool
NegocioActual::tieneNegocio(): bool          // id() !== null
NegocioActual::esSuperadmin(): bool

NegocioScope::apply(Builder, Model)
  - !hayUsuario()        → no toca la consulta
  - tieneNegocio()       → where(model->qualifyColumn('id_sesion'), id())
  - otro caso            → whereRaw('1 = 0')

PerteneceANegocio (trait)
  - bootPerteneceANegocio(): addGlobalScope(NegocioScope); creating:
      id_sesion ya puesto → respeta
      hayUsuario && tieneNegocio → id_sesion = id()
      hayUsuario && !tieneNegocio → throw SinNegocioException
      !hayUsuario → no toca (la BD rechaza NOT NULL si falta)
  - negocio(): BelongsTo(Sesion, 'id_sesion')

MembresiaNegocio::asignar(User, Sesion): void
  - el usuario ya está en otro negocio → throw UsuarioYaAsignadoException(mensaje con el nombre)
  - ya está en este negocio → no hace nada (idempotente)
MembresiaNegocio::retirar(User, Sesion): void

User
  - es_superadmin: bool (cast), fuera de $fillable
  - negocios(): BelongsToMany(Sesion, 'sesion_usuario', 'id_user', 'id_sesion')

ReporteVentasService
  - getProductsOptions(int $sesionId) · generateReport(string, int $sesionId, ?array)
  - generateMultiPeriodReport(..., int $sesionId, ...)
  - filtra explícitamente por id_sesion (no depende solo del scope)
```

Acceso a «Negocios» y «Usuarios»: `canViewAny()` = `esSuperadmin()`. Filament responde 403 a la URL
directa (CA-7). Navegación oculta para el resto.

## 4. Data model & flow

```text
sesion (id, nombre, codigo, descripcion, estado)
  └─< sesion_usuario (id_sesion, id_user UNIQUE) >── users (+ es_superadmin)
sesion ─< producto ─< inventario
sesion ─< factura · venta ─< venta_detalle · clientes · factura_electronica
         (todas con id_sesion NOT NULL; id_usuario = autor donde ya existía)
```

Migraciones en `database/migrations/alter/`:
1. `2026_10_30_000001_add_es_superadmin_to_users_table`
2. `2026_10_30_000002_add_unique_id_user_to_sesion_usuario_table`
3. `2026_10_30_000003_add_id_sesion_to_negocio_tables` (las 7 tablas, con `down()` simétrico)

Flujo de una venta (CA-2): `CreateVenta` → transacción → `lockForUpdate` sobre las filas de
`inventario` del producto (scope activo) → comprueba stock → crea `venta` y `venta_detalle`
(`id_sesion` lo pone el trait; `id_usuario` = autor) → descuenta inventario. El bloqueo evita el
stock negativo con dos ventas simultáneas (spec, edge case).

Alta de producto: `ProductoObserver::created` crea el `inventario` copiando `id_sesion` del producto
y poniendo `id_usuario` = autor.

## 5. Testing strategy

Archivo nuevo `tests/Feature/NegocioCompartidoTest.php` (`DatabaseTransactions`). Dos negocios N1
(A, C) y N2 (B) en `setUp`. Datos creados con modelos y `actingAs`.

| CA | Nivel | Qué afirma |
|---|---|---|
| 1, 2, 15 | Integración (modelos + acting as) | C ve lo que crea A; venta de C descuenta el stock que ve A; C edita y borra lo de A |
| 3 | HTTP sobre páginas Filament | Con B autenticado, el HTML de cada listado (producto, inventario, venta, cliente, factura, factura electrónica) y del dashboard no contiene marcadores únicos de N1 |
| 4 | HTTP | B pide `edit` de un producto, venta y cliente de N1 → 404 |
| 5 | Servicio | `generateReport` con `sesionId` de N1 da las mismas filas para A y C, e ignora N2 |
| 6, 7 | HTTP | Superadmin → 200 en Negocios y Usuarios; usuario normal → 403 |
| 8 | Servicio | `MembresiaNegocio::asignar` lanza con el nombre del negocio actual |
| 9, 10, 11 | Integración + HTTP | `NegocioActual::id()` null; consultas vacías; `canCreate()` false; crear lanza `SinNegocioException`; el aviso aparece (CA-9, CA-11) |
| 12 | Integración | Tras `retirar` + `refrescar()`, A ve vacío y C sigue viendo ventas de A con su autor |
| 13 | Integración | Cambiar precio de P en N1 no cambia el producto homónimo de N2 |
| 16 | Unidad | `es_superadmin` no se asigna por `fill()`; el formulario de `UserResource` no tiene ese campo |
| 17 | Integración | Superadmin asignado a N1 ve N1 y no N2 |
| 14 | Suite | `php artisan test` verde |

Tests existentes (`ReporteVentasServiceTest`, `MasVendidosApiTest`): su `setUp` asigna el usuario a
un negocio y pasa `sesionId` en lugar de `userId`. Es una adaptación al contrato nuevo, no un borrado.

## 6. Sequencing & dependencies

```text
T1 esquema ─► T2 núcleo (NegocioActual + scope + trait en modelos) ─► T3 tests existentes
                     │
     ┌───────────────┼───────────────────┬──────────────────────┐
     ▼               ▼                   ▼                      ▼
 T4 Resources    T5 Reportes        T6 Admin (Negocios,     (en paralelo,
 + guard + aviso  + Dashboard       Usuarios, membresía,     archivos disjuntos)
 + CreateVenta                      seeder)
     └───────────────┴───────────┬───────┘
                                 ▼
                     T7 tests de aceptación ─► T8 BD fresca + verify
```

T4, T5 y T6 tocan archivos disjuntos: se reparten entre subagentes, cada uno en su worktree.

## 7. Risks & open decisions

| # | Riesgo | Disparador | Impacto | Mitigación |
|---|---|---|---|---|
| R1 | Un filtro o consulta se queda sin aislar | Consulta con `DB::table()`/`DB::select()` o modelo sin trait | Fuga de datos entre negocios | Scope seguro por defecto; gate `grep` sin `DB::table`/`DB::select` en `app/` y sin `id_usuario', auth()->id()` como filtro; CA-3 renderiza cada listado con datos ajenos |
| R2 | Integrar en `dev` exige reiniciar su BD | Columnas NOT NULL en tablas con filas | `migrate` falla en `dev` | Autorizado por el dueño; se vuelve a confirmar en `ship` antes de ejecutarlo |
| R3 | Tests existentes rompen por usuario sin negocio | Scope vacío | Suite roja | T3 adapta su `setUp` |
| R4 | API n8n autenticada como usuario en tests aplica el scope | `actingAs` + ruta API | Diferencia test/producción | Aceptado: el usuario del test tiene negocio, así que el resultado coincide |
| R5 | Diff mayor que el presupuesto de revisión (400 líneas / 12 archivos) | Pint reformatea archivos de 2 espacios al tocarlos | Revisión más pesada | Commits por tarea; la revisión se hace por commit |
| R6 | Facturación electrónica con numeración global | Dos negocios reales con NIT distinto | Facturas con numeración ajena | Decisión diferida en la spec |
| R7 | `triggers/` no se carga con `migrate` | Ya pasa hoy | Ninguno nuevo | Fuera de alcance; anotado |

Rollback: la rama no toca `dev` hasta el PR. Las migraciones tienen `down()` simétrico.

## 8. Tasks

`X` = `docker exec -w /var/www/html/.worktrees/feat_negocio_compartido inventory_app`

| ID | Tarea | Depende | [P] | Traza | Done-check (literal) |
|---|---|---|---|---|---|
| T1 | Migraciones `alter/` (es_superadmin, unique id_user, id_sesion en 7 tablas); registrar `alter/` en `AppServiceProvider`; `User` con cast y `negocios()` | — | | §0, §4 | `X php artisan migrate:fresh` sin error y `X php artisan migrate:status` lista las 3 migraciones `2026_10_30_*` como Ran |
| T2 | `NegocioActual`, `NegocioScope`, `PerteneceANegocio`, `SinNegocioException`; trait en Producto, Inventario, Factura, Venta, VentaDetalle, Cliente, FacturaElectronica; `ProductoObserver` copia `id_sesion` | T1 | | §2, §3 | 7 modelos con el trait **y** `X php artisan test --filter=NegocioScopeTest` verde (N1 no ve N2; sin negocio ve vacío; crear sin negocio lanza) |
| T3 | Adaptar `setUp` de `ReporteVentasServiceTest` y `MasVendidosApiTest` (usuario con negocio) | T2 | | CA-14 | `X php artisan test --filter=MasVendidosApiTest` verde |
| T4 | Quitar filtros `id_usuario` de propiedad en Resources, widgets y `CreateVenta` (incl. `VentaResource:328`, `ProductoResource:90`, `FacturaElectronicaResource:82` y `:207`); `CreateVenta` con transacción + `lockForUpdate`; columna «Vendedor» en `VentaResource` desde `ventaDetalle.users`; trait `RequiereNegocio` en Resources operativos; aviso «sin negocio» | T2 | P | CA-1…4, 9…12, 15 | `grep -rnE "id_usuario'?, *('=', *)?auth\(\)->id\(\)" app \| grep -v "' *=> *auth"` vacío |
| T5 | `ReporteVentasService` con `sesionId`; `Dashboard` pasa el negocio activo y avisa si no hay; ajustar llamadas en `ReporteVentasServiceTest` | T3 | P | CA-5 | `X php artisan test --filter=ReporteVentasServiceTest` verde **y** `grep -rn "userId" app` vacío |
| T6 | `MembresiaNegocio` + excepción; `SesionResource` «Negocios» + `MiembrosRelationManager`; `UserResource` «Usuarios»; `SuperAdminSeeder` (usa `forceFill` para `es_superadmin`) + claves en `.env.example` | T1 | P | CA-6…8, 16 | rutas `sesions` y `users` en `X php artisan route:list --path=inventory` **y** `X php artisan test --filter=MembresiaNegocioTest` verde |
| T7 | `tests/Feature/NegocioCompartidoTest.php` con un test por CA de §5, incluidas las aserciones positivas por HTTP (C ve en los listados lo creado por A, y el autor de la venta) | T4, T5, T6 | | CA-1…17 | `X php artisan test --filter=NegocioCompartidoTest` verde |
| T8 | BD fresca con semilla + gates de verify | T7 | | DoD | `X php artisan migrate:fresh --seed` OK; `X php artisan test` verde; `X ./vendor/bin/pint --test --dirty` limpio; `grep -rn "DB::table\|DB::select" app` vacío |

Nota: entre T3 y T5, `ReporteVentasServiceTest` puede estar en rojo (cambia la firma). Se acepta; T5 lo cierra.

Reparto multiagente: T1–T3 los hace la sesión principal. T4, T5 y T6 van a tres subagentes
`developer`, cada uno en su worktree desde `feat_negocio_compartido`. La sesión principal integra,
escribe T7 y corre T8.

## 9. Analyze (2026-10-30)

PASS WITH ISSUES. Corregido aquí: done-check de T4 (dejaba pasar 4 filtros), `grep userId` en T5,
tests mínimos en T2 y T6, aserciones positivas por HTTP para CA-1/2/15, columna de autor en el listado
de ventas (CA-2/12), `forceFill` en el seeder.
