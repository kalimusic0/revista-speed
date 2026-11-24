/* CONFIGURACIÓN DE REVISTA SPEED (sitio estático en GitHub Pages) */
window.REVISTA_CONFIG = {
    nombre: 'Revista Speed',
    evento: 'Digitaliza tu negocio',
    lema: 'Casa Abierta «Digitaliza tu negocio»',
    fechaEvento: '',            // ej. '2026-12-05' para mostrar "Faltan X días" en la portada (opcional)

    // Las calificaciones y reseñas se abren AUTOMÁTICAMENTE el día del evento (fechaEvento).
    // Mientras fechaEvento esté vacía, permanecen cerradas. Para abrirlas antes, pon true.
    calificacionesAbiertas: false,

    // Calificaciones por estrellas y reseñas (servicio gratuito Supabase).
    // Mientras estén vacíos, el sitio funciona pero las estrellas aparecen "próximamente".
    // Pasos para llenarlos: ver LEEME.md, sección "Activar las estrellas".
    supabaseUrl: 'https://jhcbyjzrnomncafrueor.supabase.co',
    supabaseKey: 'sb_publishable_4RVr1_IN0zP3Rgw1DTf7CQ_xXuMEpxd'   // clave pública (publishable): la seguridad la dan las reglas del SQL
};
