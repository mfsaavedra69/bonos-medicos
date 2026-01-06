# Sistema Bonos - Frontend + API (esqueleto)

Instrucciones rápidas:

1. Crear la base de datos MySQL `bonos` y un usuario con permisos.
2. Actualizar variables de conexión en `config/db.php` o setear variables de entorno `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
3. Ejecutar las migraciones: `mysql -u root -p bonos < migrations/init.sql` (ajustar usuario/clave).
4. Crear el usuario admin: `php migrations/seed_admin.php` (crea 'admin' / 'admin123').
5. Abrir `login.php` y entrar con admin/admin123. Cambiar contraseña en el primer ingreso.

Qué ya implementé:
- Configuración de DB y migraciones (MySQL)
- Autenticación básica (login/logout, cambio de contraseña)
- Layout base con Bootstrap y DataTables
- Módulo **Especialidades**: API REST completo y frontend con DataTables y modales (ejemplo)

Siguientes pasos (que puedo seguir):
- Implementar y probar CRUD para Médicos y relaciones médico-especialidad / médico-consultorio
- Agregar módulos: Consultorios, Afiliados, Prácticas, Agenda, Turnos
- Implementar Bonos y cálculo de totales en cliente/servidor
- Agregar control de roles y permisos

Si querés, continuo implementando los demás módulos siguiendo el patrón ya creado para `especialidades`.

---

## 🔐 Migración de contraseñas en claro (importante)

He detectado que algunos usuarios podrían tener la contraseña almacenada en claro en la base de datos, mientras que el sistema actual espera hashes (password_hash / password_verify). Esto provoca que el login falle para esos usuarios.

Opciones disponibles:

1. Migración en el primer login (implementada): si un usuario se autentica con la misma contraseña que está almacenada en claro, el sistema ahora la validará y reemplazará automáticamente por un hash seguro (password_hash).

2. Migración en lote (script incluido): podés ejecutar el script `migrations/hash_plain_passwords.php` para convertir en bloque todas las contraseñas que parecen estar en claro. **Hacé un backup de la base de datos antes de ejecutar**.

Ejemplo para ejecutar el script desde la raíz del proyecto:

  php migrations/hash_plain_passwords.php

Esto recorrerá los usuarios y reemplazará contraseñas que no coincidan con un patrón típico de bcrypt por su versión hasheada.

---
