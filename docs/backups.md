# Backups de Neon

El workflow [database-backup.yml](../.github/workflows/database-backup.yml) crea un dump diario de PostgreSQL en formato `pg_dump -Fc`, valida que se pueda leer y lo cifra antes de subirlo a un repositorio privado independiente.

## Configuración inicial

1. Crear en GitHub un repositorio **privado y vacío** llamado, por ejemplo, `scomputacion-backups`.
2. Crear un fine-grained personal access token con permiso **Contents: Read and write** únicamente sobre ese repositorio. No requiere ningún permiso sobre el repositorio de la aplicación.
3. En `valeno12/scomputacion-vue` ir a **Settings → Secrets and variables → Actions** y agregar estos secretos:

   | Tipo | Nombre | Valor |
   | --- | --- | --- |
   | Secret | `NEON_DIRECT_DATABASE_URL` | La URL directa de Neon, con SSL, usada por Render. |
   | Secret | `BACKUP_REPOSITORY_TOKEN` | El token que puede escribir en el repositorio privado de backups. |
   | Secret | `BACKUP_PASSPHRASE` | Una frase larga, única y guardada fuera de GitHub. Permite descifrar los dumps. |

4. En la pestaña **Variables** agregar:

   | Nombre | Valor |
   | --- | --- |
   | `BACKUP_REPOSITORY` | `valeno12/scomputacion-backups` |

5. En **Actions → Backup de base de datos**, ejecutar **Run workflow** una vez. Verificar que aparezca un archivo `.dump.gpg` en el repositorio de backups.

No se guarda una base sin cifrar ni en el repositorio de la app ni en el de backups. El workflow conserva aproximadamente 35 copias diarias y 13 mensuales.

## Restaurar

Restaurar sólo sobre una base o rama de Neon de prueba/vacía. No restaurar directamente sobre la producción activa.

```bash
gpg --batch --decrypt --passphrase "$BACKUP_PASSPHRASE" \
  --output scomputacion.dump scomputacion-AAAA-MM-DD-HHMM.dump.gpg

pg_restore --clean --if-exists --no-owner --no-privileges \
  --dbname "$DATABASE_URL" scomputacion.dump
```

El `DATABASE_URL` de restauración debe apuntar a la base destino y usar SSL.
