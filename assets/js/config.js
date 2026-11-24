/* CONFIGURACIÓN DE REVISTA SPEED (sitio estático en GitHub Pages) */
window.REVISTA_CONFIG = {
    nombre: 'Revista Speed',
    evento: 'Casa Abierta',
    lema: 'Casa Abierta · Proyectos e ideas innovadoras',
    fechaEvento: '',            // ej. '2026-12-05' para mostrar "Faltan X días" en la portada (opcional)

    // Calificaciones por estrellas y reseñas (servicio gratuito Supabase).
    // Mientras estén vacíos, el sitio funciona pero las estrellas aparecen "próximamente".
    // Pasos para llenarlos: ver LEEME.md, sección "Activar las estrellas".
    supabaseUrl: '',            // ej. 'https://abcdefgh.supabase.co'
    supabaseKey: ''             // la clave "anon / public" (es pública por diseño; la seguridad la dan las reglas del SQL)
};
