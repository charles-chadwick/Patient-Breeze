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

const NAMED_ENTITIES = {
    '&quot;': '"',
    '&apos;': "'",
    '&lt;': '<',
    '&gt;': '>',
    '&nbsp;': ' ',
}

/**
 * Decode HTML entities to their plain-text characters without relying on the
 * DOM, so it is safe under SSR. Numeric entities are decoded first, then the
 * common named ones, with `&amp;` decoded last so escaped entities survive.
 */
function decodeHtmlEntities(text) {
    return text
        .replace(/&#(\d+);/g, (_match, code) => String.fromCodePoint(Number(code)))
        .replace(/&#x([0-9a-f]+);/gi, (_match, code) => String.fromCodePoint(parseInt(code, 16)))
        .replace(/&(?:quot|apos|lt|gt|nbsp);/g, (entity) => NAMED_ENTITIES[entity])
        .replace(/&amp;/g, '&')
}

/**
 * Turn an HTML rich-text string into a plain-text preview: strip tags, decode
 * entities, collapse whitespace, and truncate with an ellipsis.
 */
export function htmlSnippet(html, max_length = 80) {
    const without_tags = (html || '').replace(/<[^>]*>/g, ' ')
    const text = decodeHtmlEntities(without_tags).replace(/\s+/g, ' ').trim()

    return text.length > max_length ? text.slice(0, max_length) + '…' : text
}
