# Revista Speed

Revista digital para la **Casa Abierta**: portada con todos los proyectos, página completa de cada proyecto
(expositores, idea innovadora, fotos, video), calificación de 1 a 5 estrellas con reseña corta, login para
expositores y un módulo de carga que el administrador habilita o cierra.

Tecnología: **PHP 7.3+** (sin librerías ni Composer) + **SQLite** (un archivo; no hay que crear base de datos).

## Subirla al hosting (cPanel) paso a paso

1. En cPanel abre **Administrador de archivos** → carpeta `public_html` (o la de tu dominio/subdominio).
2. Sube **todo el contenido** de esta carpeta (puedes subirla comprimida en .zip y usar "Extraer").
   Asegúrate de que se vean también los archivos que empiezan con punto (`.htaccess`): en "Configuración" activa *Mostrar archivos ocultos*.
3. Da permisos de escritura a las carpetas `data/` y `uploads/` (clic derecho → Permisos → `755`; si falla, `775`).
4. Abre en el navegador `https://tudominio.com/install.php`, crea la cuenta del **administrador**.
5. **Borra `install.php`** del hosting.
6. Entra a `login.php` con tu cuenta de administrador.

Si tu hosting no tiene SQLite (raro), usa MySQL: crea la base y el usuario en cPanel → *Bases de datos MySQL*,
y en `includes/config.php` cambia `'driver' => 'mysql'` y llena `host`, `name`, `user`, `pass`. Luego abre `install.php`.

## Cómo se usa

**Administrador** (menú *Administración*)
- Habilita o cierra el **módulo de carga** de publicaciones.
- Crea las cuentas de cada curso/expositor y cambia contraseñas.
- Modera reseñas (elimina las inapropiadas).
- En *Mi panel* publica ("Publicar") u oculta los proyectos que envían los cursos.

**Expositor / curso** (*Mi panel* → *Nueva publicación*, solo mientras la carga esté habilitada)
- Llena título, curso, expositores, reseña corta, descripción, innovación, fecha y sube imagen principal + fotos de evidencia.
- Su proyecto queda *Pendiente* hasta que el administrador lo publique.

**Visitante** (sin cuenta)
- Ve la portada, filtra por curso, abre un proyecto, lo califica con estrellas y deja una reseña (una vez por navegador).

## Personalizar
- Nombre, lema, fecha de la casa abierta (cuenta regresiva): `includes/config.php`.
- Colores: variables al inicio de `assets/css/style.css`.
- La portada ordena por fecha de la más antigua a la más reciente; el visitante puede invertir el orden.

## Seguridad incluida
Contraseñas con hash, protección CSRF, consultas preparadas, freno a intentos de login, fotos validadas y
re-guardadas (no se pueden subir archivos ejecutables), `uploads/`, `data/` e `includes/` bloqueadas por `.htaccess`.
**Haz copia de `data/revista.sqlite` y de `uploads/`** antes y después del evento.
