-- REVISTA SPEED: tabla de calificaciones y reseñas (Supabase -> SQL Editor -> pegar y "Run").
-- Los visitantes pueden LEER calificaciones y CREAR una; nadie puede editar ni borrar desde la web
-- (para moderar, borra filas desde Supabase -> Table Editor -> valoraciones).

create table if not exists public.valoraciones (
    id          uuid primary key default gen_random_uuid(),
    proyecto    text not null check (char_length(proyecto) between 1 and 80),
    estrellas   int  not null check (estrellas between 1 and 5),
    nombre      text not null default '' check (char_length(nombre) <= 60),
    comentario  text not null default '' check (char_length(comentario) <= 300),
    huella      text not null check (char_length(huella) between 8 and 64),
    created_at  timestamptz not null default now(),
    unique (proyecto, huella)          -- un voto por visitante y proyecto
);

alter table public.valoraciones enable row level security;

drop policy if exists "leer calificaciones" on public.valoraciones;
create policy "leer calificaciones" on public.valoraciones
    for select to anon using (true);

drop policy if exists "enviar calificacion" on public.valoraciones;
create policy "enviar calificacion" on public.valoraciones
    for insert to anon with check (true);

-- el identificador de visitante (huella) no se expone al público
revoke all on public.valoraciones from anon;
grant select (id, proyecto, estrellas, nombre, comentario, created_at) on public.valoraciones to anon;
grant insert on public.valoraciones to anon;
