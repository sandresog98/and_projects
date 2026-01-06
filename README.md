# AndProjects App

Sistema de gestión de proyectos con interfaces separadas para colaboradores y clientes.

## 🚀 Características

### Interfaz de Colaboradores (UI)
- **Gestión de Empresas**: Crear y administrar empresas clientes con logo
- **Gestión de Usuarios**: Administrar colaboradores, admins y clientes
- **Proyectos**: Crear y gestionar proyectos por empresa
- **Tareas**: Organizar tareas dentro de proyectos con prioridades
- **Subtareas**: Dividir tareas en subtareas para tracking granular
- **Tracking de Tiempo**: Registrar horas y minutos trabajados por subtarea
- **Reuniones**: Calendario de reuniones con insights
- **Comentarios**: Sistema de comentarios en proyectos, tareas y subtareas
- **Adjuntos**: Subir archivos (imágenes, PDFs, videos) a cualquier entidad
- **Dependencias**: Definir dependencias entre tareas/subtareas (árbol de dependencias)

### Interfaz de Clientes (CX)
- **Portal minimalista**: Vista simplificada del progreso de proyectos
- **Solo lectura**: Los clientes pueden ver pero no modificar
- **Comentarios**: Pueden agregar comentarios para comunicarse
- **Calendario**: Vista de reuniones y fechas de entrega
- **Primer login**: Cambio de contraseña obligatorio en primer acceso

## 📁 Estructura del Proyecto

```
and_projects_app/
├── config/
│   └── database.php       # Configuración de base de datos
├── assets/
│   ├── css/               # Estilos CSS
│   ├── img/               # Imágenes y logos
│   └── favicons/          # Iconos
├── ui/                    # Interfaz de Colaboradores
│   ├── config/paths.php   # Rutas de la UI
│   ├── controllers/       # Controladores
│   ├── models/            # Modelos compartidos
│   ├── modules/           # Módulos funcionales
│   │   ├── empresas/
│   │   ├── usuarios/
│   │   ├── proyectos/
│   │   ├── tareas/
│   │   ├── subtareas/
│   │   ├── reuniones/
│   │   ├── comentarios/
│   │   └── adjuntos/
│   ├── pages/             # Páginas generales
│   ├── views/layouts/     # Layouts (header, footer)
│   └── utils/             # Utilidades
├── cx/                    # Interfaz de Clientes
│   ├── config/paths.php
│   ├── controllers/
│   ├── modules/
│   │   ├── proyectos/
│   │   └── calendario/
│   ├── pages/
│   ├── views/layouts/
│   └── utils/
├── sql/
│   ├── ddl.sql           # Creación de base de datos
│   └── reset_db.php      # Script para resetear BD
├── uploads/              # Archivos subidos
├── roles.json            # Roles y permisos
├── .env.example          # Variables de entorno ejemplo
└── README.md
```

## 🛠️ Instalación

### Requisitos
- PHP 8.2+
- MariaDB 11.8+
- XAMPP o servidor web compatible

### Pasos

1. **Clonar/Copiar el proyecto** en `htdocs/process/`

2. **Configurar variables de entorno**
   ```bash
   cp .env.example .env
   # Editar .env con tus credenciales
   ```

3. **Crear la base de datos**
   ```bash
   # Opción 1: Ejecutar el DDL manualmente
   mysql -u root < sql/ddl.sql
   
   # Opción 2: Usar el script PHP
   php sql/reset_db.php
   ```

4. **Configurar permisos de uploads**
   ```bash
   chmod -R 755 uploads/
   ```

5. **Acceder a las interfaces**
   - Colaboradores: `http://localhost/process/and_projects_app/ui/`
   - Clientes: `http://localhost/process/and_projects_app/cx/`

## 👥 Usuarios por Defecto

| Usuario | Email | Contraseña | Rol |
|---------|-------|------------|-----|
| Administrador | admin@andprojects.com | Admin123! | admin |
| Colaborador Demo | colaborador@andprojects.com | Admin123! | colaborador |
| Cliente Demo | cliente@empresa.com | Admin123! | cliente* |

*El cliente debe cambiar la contraseña en su primer inicio de sesión.

## 🎨 Tema Visual

- **Tema oscuro** por defecto para ambas interfaces
- Colores corporativos:
  - Azul Primario: `#55A5C8`
  - Verde Secundario: `#9AD082`
  - Gris Terciario: `#B1BCBF`
  - Azul Oscuro: `#35719E`
  - Púrpura Accent: `#6A0DAD`

## 📊 Cálculo de Avance

El avance se calcula automáticamente:
- **Subtareas**: Completada = 100%, En progreso = 50%, Pendiente = 0%
- **Tareas**: Promedio del avance de subtareas
- **Proyectos**: Promedio del avance de tareas

## 📎 Límites de Archivos

| Tipo | Tamaño Máximo |
|------|---------------|
| Imágenes | 5 MB |
| PDFs | 10 MB |
| Videos | 100 MB |

Los archivos se renombran automáticamente usando el formato:
`[tipo]_[id]_[timestamp]_[random].[ext]`

## 🔒 Seguridad

- Contraseñas hasheadas con bcrypt
- Sesiones separadas para UI y CX
- Validación de permisos por rol
- Archivos de uploads protegidos (requieren sesión)
- Primer login obligatorio para clientes

## 📝 Roles y Permisos

Los roles se definen en `roles.json`:

- **admin**: Acceso total a la UI
- **colaborador**: Acceso a la UI con permisos limitados
- **cliente**: Solo acceso a la CX (visualización)

## 🗓️ Módulo de Reuniones

- Tipos: Presencial, Virtual, Híbrida
- Vista de calendario integrado (FullCalendar)
- Registro de participantes
- Campo de insights/conclusiones

## 📞 Soporte

Para reportar bugs o solicitar funcionalidades, contactar al equipo de desarrollo.

