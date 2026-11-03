# SDD decisions log (append-only)

| Fecha | Fase | Decisión | Opciones | Por qué |
|---|---|---|---|---|
| 2026-10-30 | specify | Spec `negocio-compartido`: un usuario pertenece a un solo negocio. | Uno solo · varios con selector | Más simple y menos riesgo de mezclar datos; varios negocios queda diferido. |
| 2026-10-30 | specify | Se comparte todo dentro del negocio: inventario, productos, ventas, facturas, clientes, reportes, dashboard. | Todo · parcial | Un negocio opera sobre un único conjunto de datos. |
| 2026-10-30 | specify | Superadministrador = usuario marcado en el mismo panel Filament; no ve datos de negocios. | Mismo panel · panel aparte | Reutiliza el panel existente; evita mezclar datos por error. |
| 2026-10-30 | specify | Datos actuales son de prueba: se autoriza reiniciar la BD de desarrollo (`migrate:fresh`), nunca en tests. | Migrar datos · reiniciar | Autorizado por el dueño; la constitución (7) sigue prohibiéndolo en tests. |
| 2026-10-30 | specify | Cadena en autopiloto, aprobada por el dueño. | Autopiloto · por fases | Elección explícita del dueño. |
| 2026-10-30 | plan | Aislamiento por global scope (`NegocioScope`) en 7 modelos, con columna `id_sesion` NOT NULL. | Global scope · tenancy de Filament · `where` manuales | Seguro por defecto; cubre widgets, servicios y búsqueda por URL; la tenancy de Filament solo cubre Resources y cambia las URL. |
| 2026-10-30 | plan | `id_usuario` se conserva como autor. | Conservar · eliminar | La spec exige mostrar quién hizo cada venta. |
| 2026-10-30 | plan | Se registra `database/migrations/alter/` con `loadMigrationsFrom`; `triggers/` no. | Registrar · migraciones en raíz | La constitución (10) manda `alter/`, pero nada la cargaba. Cargar `triggers/` cambiaría el comportamiento del stock. |
| 2026-10-30 | plan | La rama usa la BD `manage_inventory_negocio`, no `manage_inventory_db`. | BD aparte · BD de dev | Otra sesión trabaja sobre `dev` y su BD; reiniciarla la rompería. |
| 2026-10-30 | review | Se corrige en esta rama la fuga del PDF de factura electrónica, aunque es previa. | Corregir ahora · dejar aparte | Contradice el objetivo de aislamiento de la spec. |
| 2026-10-30 | review | Quedan fuera: edición de ventas sin ajuste de stock (triggers no cargados), `FilamentUser`, errores 500 en carreras raras. | Incluir · diferir | Son previos y no los pide la spec; cada uno merece su propio cambio. |
