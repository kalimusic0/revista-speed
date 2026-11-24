# Revista Speed (sitio estático para GitHub Pages)

Revista digital para la **Casa Abierta**: portada con todos los proyectos, página completa de cada uno
(expositores, idea innovadora, fotos, video), calificación por estrellas y reseñas.
Publicada en **https://revista-speed.gedetca.com**.

Es un sitio estático (HTML + CSS + JS): no necesita servidor ni base de datos propia.
La versión con PHP, login y panel de carga está en la rama `version-php` de este repositorio.

## Estructura
| Archivo | Para qué sirve |
|---|---|
| `data/proyectos.json` | **Aquí viven los proyectos** (título, curso, expositores, reseña, fotos…) |
| `img/proyectos/<id>/` | Fotos de cada proyecto (`portada.jpg` + evidencias) |
| `generador.html` | Formulario que prepara el texto para pegar en `proyectos.json` |
| `assets/js/config.js` | Nombre, fecha del evento y claves de las estrellas |
| `supabase.sql` | Tabla de calificaciones (se pega una vez en Supabase) |

## Agregar un proyecto (sin programar)
1. Abre `https://revista-speed.gedetca.com/generador.html`, llena el formulario y pulsa **Generar**.
2. En GitHub abre `data/proyectos.json` → lápiz (editar) → pega el texto del paso 1 dentro de los corchetes `[ ]`
   (si ya hay proyectos, separa con una coma `,`) → **Commit changes**.
3. En GitHub abre `img/proyectos/` → **Add file → Upload files**, crea la carpeta con el nombre que indicó el generador
   (ej. `img/proyectos/mi-proyecto/portada.jpg`) y sube las fotos → **Commit changes**.
4. En 1–2 minutos aparece en la revista.

Consejos: fotos horizontales, JPG, de menos de 1,5 MB (reduce las del celular antes de subirlas).
Borra los dos proyectos de ejemplo de `proyectos.json` y de `img/proyectos/`.

El orden de la portada sigue el campo `fecha` (de la más antigua a la más reciente; el visitante puede invertirlo).

## Activar las estrellas y reseñas (Supabase, gratis)
1. Crea una cuenta en https://supabase.com → **New project** (anota la contraseña de la base de datos, no se usa aquí).
2. Cuando termine de crearse: **SQL Editor → New query**, pega todo `supabase.sql` y pulsa **Run**.
3. **Project Settings → API**: copia **Project URL** y la clave **anon public**.
4. Edita `assets/js/config.js` en GitHub y pega esos dos valores en `supabaseUrl` y `supabaseKey` → **Commit changes**.

La clave *anon* es pública por diseño: las reglas del SQL solo permiten leer calificaciones y enviar una nueva
(un voto por navegador y proyecto). Para borrar una reseña inapropiada: Supabase → **Table Editor → valoraciones** → eliminar la fila.

## Dominio
El archivo `CNAME` contiene `revista-speed.gedetca.com`. En el DNS de `gedetca.com` debe existir un registro
**CNAME** `revista-speed` → `kalimusic0.github.io` (igual que `www` y `descargas`).
En GitHub: repositorio → **Settings → Pages** → Source: *Deploy from a branch* → `main` / `(root)` → activar **Enforce HTTPS**.
