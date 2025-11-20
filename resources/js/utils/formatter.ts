// utils/formatters.ts

export const formatDate = (
  date: string | Date,
  options?: {
    includeTime?: boolean;
    includeSeconds?: boolean;
  },
): string => {
  const { includeTime = true, includeSeconds = false } = options || {};

  const dateConfig: Intl.DateTimeFormatOptions = {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  };

  if (includeTime) {
    dateConfig.hour = '2-digit';
    dateConfig.minute = '2-digit';
    dateConfig.hour12 = false; // ✅ AGREGAR ESTO - Formato 24hs

    if (includeSeconds) {
      dateConfig.second = '2-digit';
    }
  }

  return new Date(date).toLocaleString('es-AR', dateConfig);
};

export const formatMoney = (amount: number | null | undefined): string => {
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
  }).format(amount || 0);
};
