---
type: spec
title: Spec — Inventario compartido por negocio
description: WHAT y WHY para que varios usuarios de un mismo negocio compartan inventario, ventas, clientes, facturas y reportes, aislados de otros negocios.
tags: [sdd, spec, multi-negocio, aislamiento]
timestamp: 2026-10-30T12:00:00Z
topic: sdd
slug: negocio-compartido
status: aprobada en autopiloto (no punto por punto)
---

# Spec — Inventario compartido por negocio

> Slug: `negocio-compartido` · Status: aprobada en autopiloto · Created: 2026-10-30
> Inherits: [constitution](../constitution.md)
> Rama: `feat_negocio_compartido` (worktree `.worktrees/feat_negocio_compartido`)

**Vocabulario.** «Negocio» es lo que el sistema ya llama «sesión». Un negocio agrupa a los
usuarios que trabajan juntos sobre los mismos datos.

## Problem & why

Hoy cada usuario solo ve los datos que él mismo creó: inventario, productos, ventas, clientes,
facturas y reportes. Un negocio con dos o más personas (por ejemplo, el dueño y un vendedor) no
puede trabajar sobre un solo inventario. O bien comparten una cuenta, y se pierde quién hizo cada
venta, o bien cada uno lleva un inventario separado que nunca cuadra con el stock real.

## Cost of not building it

- Un negocio con varios empleados no puede usar el sistema sin compartir contraseña.
- Con una cuenta compartida no se sabe qué empleado registró cada venta, y quitarle el acceso a
  uno obliga a cambiar la clave de todos.
- El stock que ve cada persona no es el stock real si trabajan en cuentas distintas. Eso lleva a
  vender productos agotados.
- El producto no puede ofrecerse a negocios con más de una persona, que es el caso normal.

## The cheapest alternative

Que todos los empleados de un negocio usen **la misma cuenta**. Funciona hoy sin tocar nada. No
basta porque: (1) se pierde la autoría de cada venta, (2) no se puede quitar el acceso a una sola
persona, y (3) obliga a compartir credenciales, algo que el piso de seguridad del proyecto
desaconseja.

## Goals

- Todos los usuarios de un mismo negocio ven y modifican **los mismos** datos: inventario,
  productos, ventas, facturas, facturas electrónicas, clientes, reportes Excel y dashboard.
- Ningún usuario ve ni modifica datos de un negocio al que no pertenece, por ninguna pantalla,
  reporte, widget ni exportación.
- Cada venta y cada registro sigue mostrando **qué usuario** lo hizo.
- Un superadministrador crea y desactiva negocios, crea usuarios, y asigna o retira a cada usuario
  de un negocio desde el propio panel, sin tocar la base de datos.

## Non-goals / out of scope

- **Un usuario en varios negocios** a la vez, o un selector de negocio activo. En este ciclo un
  usuario pertenece a un solo negocio.
- **Roles dentro del negocio** (dueño, vendedor, observador). Todos los miembros tienen los mismos
  permisos.
- **Que el superadministrador vea los datos de todos los negocios.** Solo gestiona negocios y
  miembros.
- **Migrar los datos actuales.** Son de prueba. La base de desarrollo se reinicia (autorizado por
  el dueño el 2026-10-30).
- **Autoregistro o invitaciones** por correo. Solo el superadministrador da de alta usuarios.
- **Rangos de numeración DIAN y credenciales de facturación electrónica por negocio.** Siguen
  siendo comunes a todo el sistema (ver *decisión diferida*).
- **Cambiar la API de n8n.** Sigue respondiendo como hoy (ver *suposición tomada*).
- **Una clave de API de n8n por negocio.**
- **Actualización en tiempo real** de pantallas abiertas. Los cambios se ven en la siguiente carga.

## Users & context

- **Miembro de negocio** (dueño o empleado): entra al panel para vender, registrar stock, crear
  clientes, facturar y sacar reportes. Necesita ver lo mismo que sus compañeros, al momento.
- **Superadministrador** (el dueño del sistema, Nicolas): da de alta negocios y usuarios. No opera
  el día a día de ningún negocio, salvo que se asigne a uno como miembro normal.
- **Integración n8n**: consulta inventario y más vendidos con una clave de API.

## Behaviour

- **Main path**
  - Un miembro entra al panel y ve todo lo de su negocio, lo haya creado él o un compañero.
  - Lo que un miembro registra (producto, stock, venta, cliente, factura) lo ven al instante los
    demás miembros del mismo negocio en su siguiente carga de página o petición.
  - Cada venta muestra qué miembro la registró.
  - El superadministrador ve una sección «Negocios». Ahí crea y desactiva negocios, crea usuarios, y
    asigna o retira a cada usuario de un negocio. Los demás usuarios no ven esa sección.
- **Edge cases**
  - Usuario sin negocio que no es superadministrador: puede iniciar sesión, pero ve un aviso
    claro («no perteneces a ningún negocio, contacta al administrador»). No ve datos y no puede
    crear registros.
  - Superadministrador que no es miembro de ningún negocio: las secciones de operación le salen
    vacías. No ve datos de ningún negocio y no puede crear registros de operación.
  - Negocio desactivado: sus miembros quedan como si no tuvieran negocio (mismo aviso). Los datos se
    conservan.
  - Usuario retirado de un negocio: deja de ver los datos de ese negocio en su siguiente petición.
    Sus ventas pasadas siguen mostrando su nombre.
  - El mismo producto o el mismo cliente (misma identificación) puede existir en dos negocios. Son
    registros independientes; cambiar uno no afecta al otro. La regla de identificación de
    clientes no cambia: hoy no se exige que sea única, y este ciclo no lo añade.
  - Dos miembros venden a la vez el último ejemplar de un producto: el stock nunca queda negativo y
    uno de los dos recibe el error de stock insuficiente que ya existe hoy.
- **Error paths**
  - Intentar asignar un usuario que ya pertenece a otro negocio: el panel lo impide con un mensaje
    que nombra el negocio actual del usuario.
  - Un miembro abre por URL directa un registro de otro negocio: recibe «no encontrado». La
    respuesta no confirma que el registro exista.
  - Un usuario que no es superadministrador abre por URL directa la sección «Negocios»: acceso
    denegado.

## Acceptance criteria

- **CA-1** Given A y C son miembros del negocio N1, When A registra el producto P con 10 unidades,
  Then C ve P con 10 unidades en inventario y en productos.
- **CA-2** Given A y C en N1, When C vende 3 unidades de P, Then A ve 7 unidades disponibles y ve la
  venta, y la venta muestra a C como autor.
- **CA-3** Given B es miembro del negocio N2 y N1 tiene productos, ventas, clientes y facturas,
  When B abre cualquier listado, widget, dashboard o reporte, Then no aparece ningún dato de N1.
- **CA-4** Given B en N2 y un registro R de N1, When B abre la URL directa de ver o editar R, Then
  recibe «no encontrado» (404).
- **CA-5** Given A y C en N1, When cada uno genera el reporte Excel de ventas del mismo periodo,
  Then los dos archivos tienen las mismas filas y los mismos totales.
- **CA-6** Given el superadministrador, When entra al panel, Then ve la sección «Negocios» y puede
  crear un negocio, crear un usuario, asignarlo a ese negocio, retirarlo y desactivar el negocio.
- **CA-7** Given un usuario que no es superadministrador, When entra al panel, Then no ve la sección
  «Negocios», y si abre su URL recibe acceso denegado (403).
- **CA-8** Given un usuario U ya asignado a N1, When el superadministrador intenta asignarlo a N2,
  Then la asignación se rechaza con un mensaje que nombra N1.
- **CA-9** Given un usuario sin negocio que no es superadministrador, When entra al panel, Then ve
  el aviso de «sin negocio», los listados están vacíos y no puede crear registros.
- **CA-10** Given el superadministrador sin negocio asignado y datos en N1 y N2, When abre
  inventario, ventas o clientes, Then no ve ningún registro y no puede crear registros de operación.
- **CA-11** Given N1 desactivado, When A (miembro de N1) entra al panel, Then se comporta como CA-9.
- **CA-12** Given A creó ventas en N1, When el superadministrador retira a A de N1, Then A deja de ver
  datos de N1, y C sigue viendo las ventas de A con el nombre de A.
- **CA-13** Given P en N1 y un producto con el mismo nombre en N2, When un miembro de N1 cambia el
  precio o el stock de su P, Then el producto de N2 no cambia.
- **CA-14** Given la suite de tests existente (14 tests en la línea base), When se aplica el cambio,
  Then la suite pasa en verde, sin tests borrados ni saltados (constitución, principio 6).

## Points to clarify

- **suposición tomada** — La API de n8n no cambia en este ciclo: sigue devolviendo lo mismo que hoy,
  que ya incluye datos de todos los usuarios. *Base:* su clave es global y hoy ya cruza usuarios;
  nadie pide filtrar por negocio todavía (YAGNI). *Riesgo:* si n8n atiende a varios negocios reales,
  sus respuestas mezclan datos. En ese caso hará falta un filtro por negocio, obligatorio.
- **suposición tomada** — El superadministrador se distingue con una marca en el propio usuario y
  usa el mismo panel que todos. *Base:* decisión del dueño (opción A, 2026-10-30). *Riesgo:* bajo;
  si luego hace falta un panel aparte, la marca se reutiliza.
- **suposición tomada** — Un usuario sin negocio puede iniciar sesión y ve un aviso, en lugar de
  ver rechazado su login. *Base:* un login rechazado sin explicación genera consultas de soporte.
  *Riesgo:* bajo; cambiarlo solo afecta a CA-9 y CA-11.
- **suposición tomada** — Los catálogos comunes (municipios, tributos, unidades de medida) siguen
  siendo globales y no pertenecen a ningún negocio. *Base:* son datos oficiales iguales para todos.
  *Riesgo:* ninguno conocido.
- **suposición tomada** — El primer superadministrador se crea al reiniciar la base, con datos de
  `.env`, porque la sección «Negocios» necesita un superadministrador para existir. *Base:* sin él
  nadie podría crear el primer negocio. *Riesgo:* bajo.
- **decisión diferida** — Rangos de numeración DIAN y credenciales de facturación electrónica por
  negocio. En este ciclo siguen siendo comunes. *Riesgo:* dos negocios reales con NIT distinto no
  pueden facturar electrónicamente con su propia numeración hasta resolverlo.
- **decisión diferida** — Roles dentro del negocio (la columna de rol ya está comentada en el
  esquema). Fuera de este ciclo.
- **área no formulable** — Qué significa «borrar un negocio» con datos y facturas electrónicas
  emitidas. Por ahora solo existe desactivar.

## Revisions

- 2026-10-30 — Primera versión, escrita tras la ronda de preguntas con el dueño: un negocio por
  usuario, se comparte todo, datos actuales de prueba (se reinicia la base), superadministrador
  como usuario marcado en el mismo panel, sin acceso a datos de negocios. Aprobada en autopiloto.
- 2026-10-30 — Revisión con ojos frescos (5 puntos, todos aceptados): el superadministrador sin
  negocio no crea registros (CA-10); desactivar negocio y retirar miembro entran en Goals y CA-6; se
  quita el filtro de n8n por YAGNI (la API no cambia); «al instante» pasa a «en la siguiente carga»;
  la regla de identificación de cliente no cambia (comprobado: hoy no se exige unicidad).
