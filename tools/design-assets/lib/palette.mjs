/**
 * QuickCart brand palette and product colour variants.
 * Variant keys double as the `qc_color` swatch options created in Magento.
 */
export const BRAND = {
    primary: '#2563EB',
    primaryDark: '#1D4ED8',
    primaryLight: '#3B82F6',
    secondary: '#111827',
    accent: '#F59E0B',
    background: '#F8FAFC',
    white: '#FFFFFF',
};

/** a = light body tone, b = dark body tone, edge = metal frame, bg = studio backdrop */
export const COLORS = {
    midnight: { label: 'Midnight Black', hex: '#111827', a: '#4B5563', b: '#0B1120', edge: '#6B7280', key: '#1F2937', bg: ['#DCE4F5', '#F4F7FC'] },
    silver:   { label: 'Titan Silver',   hex: '#CBD5E1', a: '#F8FAFC', b: '#94A3B8', edge: '#E2E8F0', key: '#E2E8F0', bg: ['#E2E8F0', '#F8FAFC'] },
    blue:     { label: 'Glacier Blue',   hex: '#3B82F6', a: '#60A5FA', b: '#1D4ED8', edge: '#BFDBFE', key: '#1E40AF', bg: ['#D6E6FE', '#F0F6FF'] },
    gold:     { label: 'Sunset Gold',    hex: '#F59E0B', a: '#FDE68A', b: '#B45309', edge: '#FEF3C7', key: '#92400E', bg: ['#FDEFC8', '#FFFBEB'] },
    sage:     { label: 'Sage Green',     hex: '#10B981', a: '#6EE7B7', b: '#047857', edge: '#D1FAE5', key: '#065F46', bg: ['#CFF5E4', '#F0FDF7'] },
    rose:     { label: 'Rose Pink',      hex: '#EC4899', a: '#F9A8D4', b: '#BE185D', edge: '#FCE7F3', key: '#9D174D', bg: ['#FBDDEC', '#FDF4F9'] },
    violet:   { label: 'Cosmic Violet',  hex: '#8B5CF6', a: '#C4B5FD', b: '#6D28D9', edge: '#EDE9FE', key: '#5B21B6', bg: ['#E6E0FD', '#F7F5FF'] },
    white:    { label: 'Pearl White',    hex: '#F8FAFC', a: '#FFFFFF', b: '#CBD5E1', edge: '#F1F5F9', key: '#E2E8F0', bg: ['#DCEEFB', '#F8FBFE'] },
    red:      { label: 'Crimson Red',    hex: '#EF4444', a: '#FCA5A5', b: '#B91C1C', edge: '#FEE2E2', key: '#991B1B', bg: ['#FBDCDC', '#FEF5F5'] },
    teal:     { label: 'Ocean Teal',     hex: '#14B8A6', a: '#5EEAD4', b: '#0F766E', edge: '#CCFBF1', key: '#115E59', bg: ['#CDF4EF', '#F0FDFB'] },
};
