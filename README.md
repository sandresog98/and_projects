# AndProjects App

Sistema de gestión de proyectos con interfaces separadas para colaboradores (UI) y clientes (CX). Diseño moderno con tema oscuro monocromático y efectos visuales elegantes.

![AndProjects](assets/img/logo-horizontal.png)

## ✨ Características Principales

### 🖥️ Interfaz de Colaboradores (UI)

- **Dashboard Interactivo**: Resumen de proyectos, tareas, reuniones y horas con estadísticas en tiempo real
- **Gestión de Empresas**: Crear y administrar empresas clientes con logo personalizado
- **Gestión de Usuarios**: Administrar colaboradores, administradores y clientes
- **Proyectos**: Crear y gestionar proyectos por empresa con seguimiento de avance
- **Tareas**: Organizar tareas dentro de proyectos con prioridades y estados
- **Subtareas**: Dividir tareas en subtareas para tracking granular
- **Tracking de Tiempo**: Registrar horas y minutos trabajados por subtarea con resúmenes automáticos
- **Reuniones**: Calendario de reuniones con vista mensual (FullCalendar)
- **Comentarios**: Sistema de comentarios en proyectos, tareas y subtareas
- **Adjuntos**: Subir archivos (imágenes, PDFs, videos) a cualquier entidad
- **Perfil de Usuario**: Gestión de perfil y cambio de contraseña para todos los usuarios

### 👥 Interfaz de Clientes (CX)

- **Portal Minimalista**: Vista elegante y simplificada del progreso de proyectos
- **Dashboard Personalizado**: Resumen de proyectos, tareas y horas de la empresa
- **Visualización de Proyectos**: Ver detalles de proyectos con tareas y subtareas expandibles
- **Tracking de Horas**: Visualización del progreso de horas registradas vs estimadas
- **Comentarios**: Los clientes pueden agregar comentarios para comunicarse con el equipo
- **Calendario**: Vista de reuniones y fechas importantes
- **Primer Login Seguro**: Cambio de contraseña obligatorio en primer acceso

## 🎨 Diseño Visual

### Tema Monocromático
El sistema utiliza un diseño moderno con paleta monocromática (blanco y negro) que proporciona una experiencia visual elegante y profesional.

### Efectos Visuales
- **Glow Effect**: Borde animado que rota entre colores (blanco → gris → azul oscuro → rojo oscuro) al interactuar con elementos
- **Partículas de Fondo**: Efecto sparkles con tsParticles para un fondo dinámico
- **Animaciones Suaves**: Transiciones y animaciones CSS para una experiencia fluida
- **Glass Morphism**: Efectos de transparencia y blur en cards y elementos

### Paleta de Colores
| Color | Código | Uso |
|-------|--------|-----|
| Blanco Puro | `#FFFFFF` | Textos principales, acentos |
| Gris Claro | `#C0C0C0` | Textos secundarios |
| Gris Medio | `#8A8A8A` | Textos muted |
| Negro Puro | `#000000` | Fondo principal |
| Azul Oscuro | `#1E3A5F` | Efecto glow |
| Rojo Oscuro | `#7F1D1D` | Efecto glow |

### Colores de Acento
| Color | Código | Uso |
|-------|--------|-----|
| Verde Success | `#4ADE80` | Estados completados |
| Amarillo Warning | `#FBBF24` | Alertas, pendientes |
| Rojo Danger | `#F87171` | Errores, cancelados |
| Azul Info | `#60A5FA` | Información, en progreso |

## 📁 Estructura del Proyecto

```
and_projects_app/
├── config/
│   └── database.php          # Configuración de base de datos
├── assets/
│   ├── css/
│   │   └── app.css           # Estilos globales con efectos glow
│   ├── img/                  # Imágenes y logos
│   ├── js/                   # Scripts JavaScript
│   ├── favicons/             # Iconos del sitio
│   └── plantillas/           # Plantillas descargables
├── ui/                       # Interfaz de Colaboradores
│   ├── config/paths.php      # Rutas y funciones helper de la UI
│   ├── controllers/          # Controladores de autenticación
│   ├── models/               # Modelos compartidos
│   │   ├── UserModel.php     # Modelo de usuarios
│   │   └── TiempoModel.php   # Modelo de tracking de horas
│   ├── modules/              # Módulos funcionales
│   │   ├── empresas/         # CRUD de empresas
│   │   ├── usuarios/         # CRUD de usuarios
│   │   ├── proyectos/        # Gestión de proyectos
│   │   ├── tareas/           # Gestión de tareas
│   │   ├── subtareas/        # Gestión de subtareas
│   │   ├── reuniones/        # Calendario y reuniones
│   │   ├── perfil/           # Perfil de usuario
│   │   ├── comentarios/      # API de comentarios
│   │   └── adjuntos/         # Gestión de archivos
│   ├── pages/
│   │   └── dashboard.php     # Dashboard principal
│   ├── views/layouts/
│   │   ├── header.php        # Header con sidebar y partículas
│   │   └── footer.php        # Footer con scripts
│   ├── utils/
│   │   └── session.php       # Gestión de sesiones
│   ├── index.php             # Router principal
│   ├── login.php             # Página de login
│   └── logout.php            # Cierre de sesión
├── cx/                       # Interfaz de Clientes
│   ├── config/paths.php      # Rutas de la CX
│   ├── controllers/          # Controladores
│   ├── modules/
│   │   ├── proyectos/        # Vista de proyectos
│   │   ├── tareas/           # Vista de tareas
│   │   └── calendario/       # Calendario de reuniones
│   ├── pages/
│   │   └── dashboard.php     # Dashboard del cliente
│   ├── views/layouts/
│   │   ├── header.php        # Header con navbar y efectos
│   │   └── footer.php        # Footer
│   ├── utils/
│   │   └── session.php       # Sesiones de clientes
│   ├── index.php             # Router principal
│   ├── login.php             # Login de clientes
│   ├── cambiar-clave.php     # Cambio de contraseña inicial
│   └── logout.php            # Cierre de sesión
├── sql/
│   ├── ddl.sql               # Script de creación de BD
│   ├── reset_db.sql          # Script para resetear BD
│   └── backups/              # Backups de base de datos
├── uploads/                  # Archivos subidos
│   ├── empresas/             # Logos de empresas
│   ├── adjuntos/             # Archivos adjuntos
│   └── avatars/              # Avatares de usuarios
├── logs/                     # Logs del sistema
├── index.html                # Landing page con diseño sparkles
├── roles.json                # Definición de roles y permisos
├── .env                      # Variables de entorno (no incluido)
├── .env.example              # Ejemplo de variables de entorno
├── .gitignore                # Archivos ignorados por Git
└── README.md                 # Este archivo
```

## 🛠️ Instalación

### Requisitos
- PHP 8.2+
- MariaDB 11.8+
- XAMPP o servidor web compatible
- Navegador moderno (Chrome, Firefox, Safari, Edge)

### Pasos de Instalación

1. **Clonar/Copiar el proyecto** en `htdocs/process/`

2. **Configurar variables de entorno**
   ```bash
   cp .env.example .env
   # Editar .env con tus credenciales de base de datos
   ```

3. **Crear la base de datos**
   ```bash
   # Opción 1: Ejecutar el DDL manualmente en phpMyAdmin o terminal
   mysql -u root < sql/ddl.sql
   
   # Opción 2: Usar el script PHP
   php sql/reset_db.php
   ```

4. **Configurar permisos de uploads**
   ```bash
   chmod -R 777 uploads/
   chmod -R 777 logs/
   ```

5. **Acceder a las interfaces**
   - Landing Page: `http://localhost/process/and_projects_app/`
   - Colaboradores: `http://localhost/process/and_projects_app/ui/`
   - Clientes: `http://localhost/process/and_projects_app/cx/`

## 👥 Usuarios por Defecto

| Usuario | Email | Contraseña | Rol | Interfaz |
|---------|-------|------------|-----|----------|
| Administrador | admin@andprojects.com | Admin123! | admin | UI |
| Colaborador Demo | colaborador@andprojects.com | Admin123! | colaborador | UI |
| Cliente Demo | cliente@empresa.com | Admin123! | cliente | CX* |

*El cliente debe cambiar la contraseña en su primer inicio de sesión.

## ⏱️ Sistema de Tracking de Horas

El sistema incluye un completo módulo de tracking de tiempo:

### Registro de Tiempo
- Se registra tiempo a nivel de **subtarea**
- Campos: horas, minutos, fecha, descripción del trabajo
- El tiempo se acumula automáticamente hacia arriba

### Resúmenes Automáticos
- **Por Subtarea**: Horas registradas vs estimadas
- **Por Tarea**: Suma de todas las subtareas
- **Por Proyecto**: Suma de todas las tareas
- **Por Empresa**: Suma de todos los proyectos
- **General**: Total de todas las empresas

### Visualización
- Barras de progreso con porcentaje
- Indicador de exceso cuando se superan las horas estimadas
- Formato legible: "2h 30m"

## 📊 Cálculo de Avance

El avance se calcula automáticamente basado en estados:

| Nivel | Cálculo |
|-------|---------|
| Subtareas | Completada = 100%, En progreso = 50%, Pendiente = 0% |
| Tareas | Promedio del avance de sus subtareas |
| Proyectos | Promedio del avance de sus tareas |

### Estados Disponibles
1. **Pendiente** - Sin iniciar
2. **En Progreso** - En desarrollo
3. **Completado** - Finalizado
4. **Cancelado** - No se realizará

## 📎 Gestión de Archivos

### Límites de Tamaño
| Tipo | Tamaño Máximo |
|------|---------------|
| Imágenes (jpg, png, gif, webp) | 5 MB |
| Documentos (PDF) | 10 MB |
| Videos (mp4, webm) | 100 MB |

### Nomenclatura de Archivos
Los archivos se renombran automáticamente para evitar conflictos:
```
[tipo]_[id]_[timestamp]_[random].[ext]
Ejemplo: logo_empresa_1_abc123def.png
```

## 🔒 Seguridad

- **Contraseñas**: Hasheadas con bcrypt (PASSWORD_DEFAULT)
- **Sesiones**: Separadas para UI y CX
- **Permisos**: Validación por rol en cada módulo
- **Uploads**: Archivos protegidos, requieren sesión activa
- **Primer Login**: Cambio obligatorio de contraseña para clientes
- **Variables Sensibles**: Almacenadas en `.env` (no versionado)

## 📝 Roles y Permisos

Los roles se definen en `roles.json`:

### Admin
- Acceso total a la UI
- Gestión de usuarios
- Gestión de empresas
- Todos los módulos disponibles

### Colaborador
- Acceso a la UI con permisos limitados
- Puede gestionar proyectos, tareas y subtareas asignados
- Puede registrar tiempo
- Puede ver y crear reuniones

### Cliente
- Solo acceso a la CX (portal de clientes)
- Visualización de sus proyectos
- Puede agregar comentarios
- No puede modificar datos

## 🗓️ Módulo de Reuniones

### Tipos de Reunión
- **Presencial**: En ubicación física
- **Virtual**: Por videoconferencia
- **Híbrida**: Combinación de ambas

### Funcionalidades
- Vista de calendario integrado (FullCalendar)
- Filtros por empresa y proyecto
- Registro de participantes
- Campo de insights/conclusiones
- Colores por tipo de reunión

## 🎯 Módulos Implementados

### UI (Colaboradores)
- ✅ Dashboard con estadísticas y resumen de horas
- ✅ Empresas (CRUD completo con logo)
- ✅ Usuarios (CRUD, reset password, toggle status)
- ✅ Proyectos (CRUD, vista detallada con tareas)
- ✅ Tareas (CRUD, vista con subtareas expandibles)
- ✅ Subtareas (CRUD, registro de tiempo)
- ✅ Reuniones (CRUD, calendario)
- ✅ Perfil (editar perfil, cambiar contraseña)
- ✅ Comentarios (API para todas las entidades)
- ✅ Adjuntos (subida y descarga de archivos)

### CX (Clientes)
- ✅ Dashboard con resumen de empresa
- ✅ Proyectos (vista detallada con tareas y subtareas)
- ✅ Tareas (vista detallada con progreso de horas)
- ✅ Calendario de reuniones
- ✅ Sistema de comentarios
- ✅ Cambio de contraseña inicial

## 🌐 Compatibilidad de Navegadores

El sistema está optimizado para navegadores modernos:
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

**Nota**: El efecto glow animado utiliza `@property` de CSS que puede no funcionar en navegadores antiguos. En esos casos, se muestra un efecto de sombra estático como fallback.

## 📱 Responsive Design

Ambas interfaces (UI y CX) son completamente responsivas:
- Desktop (1200px+)
- Tablet (768px - 1199px)
- Mobile (< 768px)

## 🔧 Tecnologías Utilizadas

### Backend
- PHP 8.2
- MariaDB 11.8
- PDO para conexiones de base de datos

### Frontend
- Bootstrap 5.3
- Bootstrap Icons
- FullCalendar (calendario)
- tsParticles (efectos de partículas)
- CSS3 con variables y animaciones
- JavaScript ES6+

### Herramientas
- XAMPP como servidor local
- Git para control de versiones

## 📞 Soporte

Para reportar bugs o solicitar funcionalidades, contactar al equipo de desarrollo.

---

**AndProjects** - Sistema de Gestión de Proyectos  
Desarrollado con ❤️ para una mejor gestión de proyectos.
