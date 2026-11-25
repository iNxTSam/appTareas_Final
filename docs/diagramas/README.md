# Diagrama entidad–relación · propuesta

Modelo objetivo de AppTareas: cuentas individuales o de equipo, equipos con
roles, proyectos, tareas (de equipo o personales), responsables múltiples y
subtareas.

## Archivos

| Archivo | Uso |
|---|---|
| `er-propuesto.png` | Imagen para el informe (220 dpi) |
| `er-propuesto.svg` | Versión vectorial (zoom sin pérdida) |
| `er-propuesto.pdf` | Versión para imprimir |
| `er-propuesto.mmd` | Fuente Mermaid (se ve en GitHub o en mermaid.live) |
| `esquema-propuesto.sql` | DDL, 2.000 filas literales y 3 consultas |
| `generar_er.py` | Script que regenera PNG, SVG y PDF con matplotlib |

Regenerar:

```powershell
python docs/diagramas/generar_er.py
```

## Alcance

Se dibujan las 7 tablas del dominio. Quedan fuera las tablas de infraestructura
de Laravel: `sessions`, `personal_access_tokens`, `password_reset_tokens`,
`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` y `migrations`.

## Entidades

| Tabla | Propósito |
|---|---|
| `account` | Cuenta de acceso; puede ser individual o pertenecer a equipos |
| `team` | Equipo de trabajo |
| `team_member` | Pertenencia de una cuenta a un equipo, con rol |
| `project` | Proyecto o categoría dentro de un equipo |
| `task` | Tarea de equipo o personal, con fechas, estado y prioridad |
| `task_assignee` | Responsables de una tarea (N:M) |
| `subtask` | Checklist de una tarea, con responsable opcional |

## Relaciones

| Relación | Cardinalidad | Regla |
|---|---|---|
| `account` · `team` | N:M | vía `team_member`, con rol |
| `team` → `project` | 1:N | un proyecto pertenece a un equipo |
| `team` → `task` | 1:N opcional | la tarea puede ser personal (`team_id` nulo) |
| `project` → `task` | 1:N opcional | la tarea puede no tener proyecto |
| `account` → `task` | 1:N | `created_by` (quien la crea) |
| `task` · `account` | N:M | vía `task_assignee` |
| `task` → `subtask` | 1:N | borrado en cascada |
| `account` → `subtask` | 1:N opcional | responsable de la subtarea |

## Cuentas individuales

- Una cuenta puede existir sin ningún registro en `team_member`.
- Una **tarea personal** tiene `team_id` y `project_id` nulos; su dueño es
  `created_by`.
- El índice `ix_task_personal_calendar` cubre el calendario de esas tareas.

## Integridad

- `task.status` ∈ {`pendiente`, `en_progreso`, `completada`}.
- `task.priority` ∈ {`baja`, `media`, `alta`}.
- `CHECK (end_date >= start_date)`.
- `CHECK ((status = 'completada') = (completed_at IS NOT NULL))`.
- `CHECK (project_id IS NULL OR team_id IS NOT NULL)`: un proyecto exige equipo.
- `task.project_id` debe ser del mismo equipo: FK compuesta
  `(project_id, team_id)` → `project(project_id, team_id)`.
- `task_assignee` solo admite miembros del equipo: la aplicación lo valida.
- Borrados: `team` en cascada a `project`, `task` y `team_member`;
  `task` en cascada a `subtask` y `task_assignee`.

## Roles

- `owner`: administra el equipo y sus miembros.
- `admin`: administra proyectos y tareas.
- `member`: trabaja en las tareas asignadas.

## Convenciones

- Clave primaria: nombre de la tabla en singular + `_id` (`task_id`, `account_id`).
- Clave foránea: tabla referenciada + `_id`; si hay un rol, se antepone el rol
  (`owner_id`, `created_by`, `assignee_id`).
- Restricciones e índices con prefijo `pk_`, `fk_`, `uq_`, `ck_` e `ix_`.
- Marcas de tiempo con zona horaria (`timestamptz`).
- La tabla de cuentas se llama `account` porque `user` es palabra reservada en
  PostgreSQL.

## Datos de prueba

El script carga 2.000 filas con valores literales (sin `generate_series` ni
funciones):

| Tabla | Filas |
|---|---:|
| `account` | 60 |
| `team` | 8 |
| `team_member` | 120 |
| `project` | 32 |
| `task` | 900 |
| `task_assignee` | 400 |
| `subtask` | 480 |
| **Total** | **2.000** |

- 54 cuentas pertenecen a equipos; 6 son individuales.
- 800 tareas son de equipo y 100 son personales.

## Consultas

Tres reportes con `INNER JOIN`:

1. Tareas por persona: `account` → `task_assignee` → `task`.
2. Tareas por equipo: `team` → `task`.
3. Tareas por día: `task` agrupada por `start_date`.

## Validación

El script se ejecutó sobre una base limpia de PostgreSQL 18: creó las 7 tablas,
cargó 2.000 filas y devolvió las tres consultas. También se comprobó que rechaza:

- `end_date` anterior a `start_date` (CHECK).
- estados fuera de `pendiente / en_progreso / completada` (CHECK).
- una tarea con un proyecto de otro equipo (FK compuesta).
- un proyecto sin equipo (`ck_task_personal`).

## Diferencia con el esquema actual

| Actual | Propuesta | Motivo |
|---|---|---|
| `users` | `account` | cuentas individuales o de equipo |
| `tasks.team` varchar | `team` + `team_member` | equipos reales y roles |
| `tasks.category` varchar | `project` | filtrar, colorear y archivar |
| `tasks.responsible_id/name` | `task_assignee` | varios responsables |
| `tasks.subtasks` JSON | `subtask` | checklist consultable |
| `priority` normal/importante | baja/media/alta | coincide con la UI |
| sin CHECK | CHECK de fechas y estado | integridad en la base |
| sin `completed_at` | `completed_at` | métricas y filtros |
