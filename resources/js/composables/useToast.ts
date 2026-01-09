import { ref } from 'vue';

type ToastType = 'success' | 'error' | 'info';

interface Toast {
  id: number;
  message: string;
  type: ToastType;
  duration?: number; // Duration in milliseconds, defaults to 3000
}

const toasts = ref<Toast[]>([]);
let toastIdCounter = 0;

export function useToast() {
  const showToast = (message: string, type: ToastType = 'info', duration: number = 3000) => {
    const id = toastIdCounter++;
    toasts.value.push({ id, message, type, duration });

    setTimeout(() => {
      toasts.value = toasts.value.filter(toast => toast.id !== id);
    }, duration);
  };

  const success = (message: string, duration?: number) => showToast(message, 'success', duration);
  const error = (message: string, duration?: number) => showToast(message, 'error', duration);
  const info = (message: string, duration?: number) => showToast(message, 'info', duration);

  return {
    toasts,
    success,
    error,
    info,
  };
}