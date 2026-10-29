---
type: constitution
title: manage-inventory — Constitution
description: The non-negotiable principles every rsc-sdd phase obeys.
tags: [sdd, constitution]
timestamp: 2026-10-29T20:30:00Z
topic: sdd
version: v1.0.0
---

# manage-inventory — Constitution

> Version: v1.0.0 · Ratified: 2026-10-29 · Last amended: 2026-10-29
> Sistema de gestión de inventario y ventas con reportes y API para n8n.
> Reglas no negociables que obedece cada fase de rsc-sdd. Los comandos exactos viven en
> `02-DOCS/wiki/sdd/config.yaml`.

## 1. Stack canon

1. PHP ^8.1 con Laravel 10, Filament 3 (Livewire), Sanctum, MySQL 8.0 y Vite 5 para assets.
   Versiones fijadas en `composer.json` / `package.json`. Cambiar de framework o de motor de BD
   es una enmienda MAJOR.
2. Gestores de paquetes: Composer (PHP) y npm (assets). `composer.lock` y `package-lock.json`
   se commitean siempre.
3. Todo comando de la app (artisan, tests, Pint, composer) se ejecuta dentro del contenedor
   `app`: `docker compose exec -T app …`. El entorno se levanta con `docker-compose.yml` /
   `setup.sh`.
4. Los Excel se generan con OpenSpout (`app/Services/ReporteVentasService.php`); no se añade
   otra librería de hojas de cálculo sin enmienda.

## 2. Quality bar

5. Estilo: Laravel Pint con su preset por defecto (**4 espacios**). Todo archivo PHP que se
   cree o modifique pasa `./vendor/bin/pint --test --dirty`. El código antiguo con 2 espacios
   se migra al tocarlo, no en bloque. `.editorconfig` refleja los 4 espacios.
6. Tests: la suite completa (`php artisan test`, PHPUnit 10) pasa en verde antes de cada merge.
   Un test nuevo por cambio no es obligatorio, pero ningún test existente se borra ni se salta
   para conseguir verde.
7. Los tests que tocan la BD usan `DatabaseTransactions`. `RefreshDatabase`,
   `DatabaseMigrations` y `migrate:fresh` están prohibidos en tests: corren contra el MySQL de
   desarrollo (`manage_inventory_db`) y lo borrarían.
8. Type-hints en parámetros y retornos de todo método nuevo o modificado, salvo donde Laravel o
   Filament lo impidan.

## 3. Conventions

9. Capas: los Models solo tienen definición de tabla, relaciones, scopes y accessors; la
   lógica de negocio con varios modelos o servicios externos (p. ej. Factus) va en
   `app/Services`; los efectos secundarios tras cambios en BD van en `app/Observers`; los
   Controllers y Filament Resources no contienen lógica pesada. En Filament, `form()` y
   `table()` largos se parten en métodos privados, los Select de relación usan
   `relationship()` y la validación se declara en los componentes (`required()`, `unique()`…).
   Lógica repetida entre Resources se extrae a un Trait o Service.
10. Base de datos: tablas en singular y `snake_case` (`producto`, `venta`); PK `id`; FK
    `id_<modelo>` con `constrained()` y `onDelete` explícito; índices en columnas de filtro u
    orden. Migraciones base en `database/migrations/`, triggers SQL en
    `database/migrations/triggers/`, alteraciones en `database/migrations/alter/`.
11. Las respuestas JSON de la API usan el sobre `{ "success": bool, "data": … }` en éxito y
    `{ "success": false, "message": "…" }` en error, con el código HTTP correcto (401, 404, 422…).
12. Commits en español con Conventional Commits, **sin emoji**: `feat: …`, `fix: …`,
    `chore: …`, `refactor: …`, `test: …`, `docs: …`.

## 4. Branching & shipping

13. El trabajo sale de `dev` en ramas `feat_<nombre>` o `fix_<nombre>` y vuelve a `dev` por PR.
    `dev` llega a `main` solo por PR. Nadie (ni el agente) commitea directo en `main`.
14. **La autoría de git es humana**: commits y PR los firma Nicolas Gonzalez
    (`ng33446@gmail.com`). Sin `Co-Authored-By` de una IA ni pie "Generated with". Se aplica en
    la fase `ship`.

## 5. Security & privacy floor

15. Ningún secreto se commitea. Los secretos viven en `.env` (ignorado por git);
    `.env.example` solo lleva claves vacías o valores de ejemplo.
16. Toda ruta de `routes/api.php` está detrás de un middleware de autenticación: `n8n.auth`
    (header `X-API-KEY` contra `config('services.n8n.api_key')`) para integraciones, o
    `auth:sanctum` para usuarios. Una ruta de API pública requiere enmienda.

## 6. Knowledge & decisions

17. Toda decisión significativa se añade a `02-DOCS/wiki/sdd/decisions.md` (fecha, opciones,
    por qué). Esta constitución es el registro de decisiones de mayor rango y la única guía
    de estándares del proyecto.

## Definition of Done (la barra que corre `verify`)

Un cambio se integra solo cuando se cumple TODO:

- [ ] `docker compose exec -T app php artisan test` en verde, sin tests borrados ni saltados (6).
- [ ] `docker compose exec -T app ./vendor/bin/pint --test --dirty` limpio (5).
- [ ] Tests de BD con `DatabaseTransactions`; nada de `RefreshDatabase` (7).
- [ ] Type-hints, capas, convenciones de BD y sobre JSON respetados (8-11).
- [ ] Migraciones nuevas aplican con `php artisan migrate` sin error (10).
- [ ] Commit en español, Conventional Commits, sin emoji (12).
- [ ] En rama `feat_*`/`fix_*` → PR a `dev`; autoría de Nicolas, sin trailer de IA (13-14).
- [ ] Sin secretos en el diff; rutas de API autenticadas (15-16).
- [ ] Decisiones significativas registradas (17).

## Amendment log (append-only)

| Date | Version | Change | Why |
|------|---------|--------|-----|
| 2026-10-29 | draft | Draft generated by `rsc onboard`. | The SDD floor needs a constitution; ratification is the `constitution` phase's. |
| 2026-10-29 | v1.0.0 | Constitución inicial: 17 principios. Stack corregido a Laravel/PHP (onboard detectó "node, python" por error). Indentación de 4 espacios (Pint), `.editorconfig` actualizado. Absorbe las reglas de arquitectura, BD y Filament del antiguo `GEMINI.md` (eliminado por el dueño). Barra de tests: suite verde, sin test obligatorio por cambio. Commits sin emoji ni coautoría de IA. | Ratificada explícitamente por Nicolas Gonzalez tras la entrevista de `constitution`. |
