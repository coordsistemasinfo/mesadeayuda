# API para Crear o Actualizar Usuarios en OSTicket

Este script permite crear o actualizar usuarios en una instalación de OSTicket mediante una solicitud HTTP POST con un cuerpo en formato JSON. Si el usuario ya existe en la base de datos, la información es actualizada. Si no, se crea un nuevo registro de usuario.

## Requisitos

- OSTicket instalado y configurado.
- Base de datos accesible de OSTicket.
- Autenticación básica requerida con credenciales específicas.

## Uso

### URL del Endpoint

```
POST /api/osticket_user.php
```

### Autenticación

Este API requiere autenticación básica. Las credenciales válidas son:

- **Usuario**: `ver en el archivo user.api.php`
- **Contraseña**: `ver en el archivo user.api.php`

Se recomienda cambiar estas credenciales por seguridad.

### Encabezados Requeridos

- `Content-Type: application/json`

### Formato de Solicitud

La solicitud debe enviarse con el método `POST` y contener un cuerpo JSON con los siguientes campos:

| Campo               | Tipo     | Descripción                                                                                                  | Obligatorio |
|---------------------|----------|--------------------------------------------------------------------------------------------------------------|-------------|
| `identificacion`     | `string` | Número de identificación del usuario.                                                                         | Sí          |
| `nombre_completo`    | `string` | Nombre completo del usuario.                                                                                  | Sí          |
| `tipo_vinculacion`   | `string` | Tipo de vinculación del usuario. Los valores permitidos son: `DOCENTE`, `ESTUDIANTE`, `ADMINISTRATIVO`, `PROVEEDOR`, `OTRO`. | Sí          |
| `correo`             | `string` | Correo electrónico del usuario.                                                                               | Sí          |
| `seccion_facultad`   | `string` | Facultad o sección a la que pertenece el usuario.                                                             | Sí          |
| `telefono_movil`     | `string` | Número de teléfono móvil del usuario.                                                                         | No          |
| `programa`           | `string` | Programa al que pertenece el usuario.                                                                         | No          |

### Ejemplo de Solicitud

```bash
curl -X POST https://mesadeayuda,unicomfacauca.edu.co/user.api.php \
  -H "Content-Type: application/json" \
  -u <usuario>:<contraseña> \
  -d '{
    "identificacion": "123456789",
    "nombre_completo": "Juan Pérez",
    "tipo_vinculacion": "DOCENTE",
    "correo": "juan.perez@universidad.edu",
    "seccion_facultad": "Ingeniería",
    "telefono_movil": "3001234567",
    "programa": "Ingeniería de Sistemas"
}'
```

### Respuesta Exitosa

Si la solicitud se procesa correctamente, se devolverá una respuesta con código HTTP 200 y el siguiente formato JSON:

```json
{
  "status": "success",
  "data": {
    "identificacion": "123456789",
    "nombre_completo": "Juan Pérez",
    "tipo_vinculacion": "DOCENTE",
    "correo": "juan.perez@universidad.edu",
    "seccion_facultad": "Ingeniería",
    "telefono_movil": "3001234567",
    "programa": "Ingeniería de Sistemas"
  }
}
```

### Errores Comunes

1. **Autenticación Requerida** (`401 Unauthorized`): Si no se proporcionan las credenciales correctas.
2. **Credenciales Inválidas** (`403 Forbidden`): Si las credenciales proporcionadas son incorrectas.
3. **Método no Permitido** (`405 Method Not Allowed`): Si se usa un método HTTP distinto de `POST`.
4. **JSON No Válido** (`400 Bad Request`): Si el cuerpo de la solicitud no está en el formato JSON esperado o si faltan campos requeridos.
5. **Error en el Servidor** (`500 Internal Server Error`): Si ocurre algún error en la base de datos o en la transacción.

### Notas

- Este API permite tanto la creación como la actualización de usuarios. Si el correo electrónico del usuario ya existe en la base de datos de OSTicket, los datos asociados a ese usuario serán actualizados.
- El campo `tipo_vinculacion` solo acepta los siguientes valores: `DOCENTE`, `ESTUDIANTE`, `ADMINISTRATIVO`, `PROVEEDOR`, `OTRO`.

### Base de Datos Involucrada

Este script interactúa con las siguientes tablas en la base de datos de OSTicket:

- `ost_user`: Contiene la información principal del usuario.
- `ost_user_email`: Almacena las direcciones de correo electrónico de los usuarios.
- `ost_user__cdata`: Almacena información adicional del usuario, como la identificación, tipo de vinculación, teléfono y programa.

### Autor

- **Peter Emerson Pinchao** - [coordsistemasinfo@unicomfacauca.edu.co](mailto:coordsistemasinfo@unicomfacauca.edu.co)

Fecha: 2024-08-26