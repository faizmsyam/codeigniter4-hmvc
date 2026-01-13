/**
 * Build a space-delimited class string from an object map of className -> truthy/falsey.
 * Only keys with truthy values are included.
 */
export declare const buildClassNames: (flags: Record<string, unknown>) => string;
export declare const createEl: (tagName: string, attrs?: object | null, container?: HTMLElement) => HTMLElement;
