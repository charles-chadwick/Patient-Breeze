import { clsx } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs) {
  return twMerge(clsx(inputs));
}

export function formatDate(value, options = {}) {
    return new Date(value).toLocaleDateString('en-US', { timeZone: 'UTC', ...options })
}

export const DATE_SHORT = { year: 'numeric', month: 'short', day: 'numeric' }
export const DATE_LONG  = { year: 'numeric', month: 'long',  day: 'numeric' }
