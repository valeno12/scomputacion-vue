import { createGlobalState, useStorage } from '@vueuse/core';

export const usePrivacyMode = createGlobalState(
  () => useStorage('privacy-mode', false), // Por defecto false (visible)
);
