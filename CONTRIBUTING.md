# Contribuir

## Equipo y responsabilidades

| Área | Integrantes | GitHub | Carpetas |
| --- | --- | --- | --- |
| Backend y BD | Samuel Prieto, Daniela Ramírez | `@iNxTSam`, `@Dani34881` | `backend/` |
| Frontend | Anderson Lugo, Nicolás Arenas | `@Lugop67`, `@Estebaaaannxx11` | `frontend/` |
| Pruebas y UI | Jose Daniel | `@JDDAR` (invitación pendiente) | `frontend/tests/`, `docs/` |
| Infraestructura | Samuel Prieto | `@iNxTSam` | `.github/`, `compose.yaml` |

Jose Daniel debe aceptar la invitación al repositorio para que sus revisiones de QA/docs sean válidas.

## Preparación

Desde la raíz del repositorio:

```bash
npm run setup
```

Luego configura los archivos `.env` de `backend/` y `frontend/` siguiendo el [README](README.md). No subas archivos `.env`, credenciales ni `node_modules`.

## Flujo de ramas

Usamos una rama por rol:

- `backend`: equipo Backend/BD (API, modelos, migraciones y pruebas de API).
- `frontend`: equipo Frontend (Blade, CSS/JS y pruebas web).
- `testing`: QA/UI (pruebas, documentación y validación visual).
- `main`: integración estable y protegida.

Cada integrante puede trabajar directamente en su rama de rol para cambios pequeños o crear una subrama `feat/backend-...`, `feat/frontend-...` o `test/...` y fusionarla a su rama de rol.

Nombres permitidos:

| Rama | Ejemplo |
| --- | --- |
| `feat/backend-...` | `feat/backend-rate-limit` |
| `feat/frontend-...` | `feat/frontend-password-recovery` |
| `test/...` | `test/auth-cp-cases` |
| `docs/...` | `docs/repo-structure` |
| `chore/...` | `chore/ci-cleanup` |
| `hotfix/...` | `hotfix/login-500` |

Usa minúsculas, guiones y sin espacios ni acentos.

## Commits

Formato: `tipo(área): descripción`.

Ejemplos:

```text
feat(backend): agrega límite de intentos
feat(frontend): mejora validación en vivo
fix(frontend): evita acceso al panel tras logout
test(auth): agrega casos CP-15
docs: reorganiza documentación
chore(ci): separa jobs de backend y frontend
```

## Antes de abrir el PR

```bash
npm run test
npm run build
docker compose -f compose.yaml config
```

Checklist:

- [ ] Las pruebas de backend y frontend pasan.
- [ ] El frontend compila.
- [ ] No se agregaron secretos ni archivos `.env`.
- [ ] La documentación quedó actualizada.
- [ ] El PR explica qué cambia y cómo probarlo.
- [ ] El cambio está limitado a la carpeta del rol correspondiente.

## Revisión y merge

- Se requiere al menos 1 aprobación.
- Los checks `Backend - PHP 8.4` y `Frontend - PHP 8.4 + Node 24` deben estar en verde.
- Se usa **Squash and merge**.
- La rama se elimina después del merge.
- Los cambios de backend los revisa el equipo de backend; los de frontend, el equipo de frontend; QA/docs revisa `docs/` y `frontend/tests/`.
