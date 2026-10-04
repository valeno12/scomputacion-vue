import { router } from '@inertiajs/vue3';

type Origen = { url: string; scroll: number };
const key = (id?: number) => `pedidos:volver:${id ?? 'nuevo'}`;

function leerOrigen(id?: number): Origen | null {
  if (typeof window === 'undefined') return null;
  try {
    const value = JSON.parse(sessionStorage.getItem(key(id)) || 'null');
    if (!value || typeof value.url !== 'string') return null;
    const url = new URL(value.url, window.location.origin);
    if (url.origin !== window.location.origin || url.pathname !== '/Pedido')
      return null;
    return {
      url: url.pathname + url.search,
      scroll: Math.max(0, Number(value.scroll) || 0),
    };
  } catch {
    return null;
  }
}

export function recordarListadoPedidos(id?: number) {
  try {
    sessionStorage.setItem(
      key(id),
      JSON.stringify({
        url: window.location.pathname + window.location.search,
        scroll: window.scrollY,
      }),
    );
  } catch {
    /* Navigation still works if browser storage is unavailable. */
  }
}

export function usePedidoNavigation(
  id?: number,
  estado?: number | null | (() => number | null),
) {
  const fallback = () => {
    const actual = typeof estado === 'function' ? estado() : estado;
    return actual === 5
      ? '/Pedido?estado=entregados'
      : actual === 4
        ? '/Pedido?estado=finalizados'
        : '/Pedido';
  };
  const listadoUrl = () => leerOrigen(id)?.url || fallback();
  const volverAlListado = () => {
    const origen = leerOrigen(id);
    router.visit(origen?.url || fallback(), {
      onSuccess: () =>
        requestAnimationFrame(() =>
          window.scrollTo({ top: origen?.scroll || 0, behavior: 'instant' }),
        ),
    });
  };
  return { listadoUrl, volverAlListado };
}
