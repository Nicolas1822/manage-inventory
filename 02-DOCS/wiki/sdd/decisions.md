# SDD decisions log (append-only)

| Fecha | Fase | Decisión | Opciones | Por qué |
|---|---|---|---|---|
| 2026-10-30 | specify | Spec `negocio-compartido`: un usuario pertenece a un solo negocio. | Uno solo · varios con selector | Más simple y menos riesgo de mezclar datos; varios negocios queda diferido. |
| 2026-10-30 | specify | Se comparte todo dentro del negocio: inventario, productos, ventas, facturas, clientes, reportes, dashboard. | Todo · parcial | Un negocio opera sobre un único conjunto de datos. |
| 2026-10-30 | specify | Superadministrador = usuario marcado en el mismo panel Filament; no ve datos de negocios. | Mismo panel · panel aparte | Reutiliza el panel existente; evita mezclar datos por error. |
| 2026-10-30 | specify | Datos actuales son de prueba: se autoriza reiniciar la BD de desarrollo (`migrate:fresh`), nunca en tests. | Migrar datos · reiniciar | Autorizado por el dueño; la constitución (7) sigue prohibiéndolo en tests. |
| 2026-10-30 | specify | Cadena en autopiloto, aprobada por el dueño. | Autopiloto · por fases | Elección explícita del dueño. |
