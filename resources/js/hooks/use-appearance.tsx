import { useSyncExternalStore } from 'react';

export type Appearance = 'light' | 'dark' | 'system';
const eventName = 'carverse:appearance';
const isAppearance = (value: string | null): value is Appearance => value === 'light' || value === 'dark' || value === 'system';
let current: Appearance = 'system';

function readSaved(): Appearance {
    try {
        const value = localStorage.getItem('appearance');
        return isAppearance(value) ? value : 'system';
    } catch {
        return current;
    }
}
function applyTheme(appearance: Appearance) {
    const dark = appearance === 'dark' || (appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.classList.toggle('dark', dark);
    document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
}
function notify() {
    window.dispatchEvent(new Event(eventName));
}
export function initializeTheme() {
    current = readSaved();
    applyTheme(current);
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => applyTheme(current));
    window.addEventListener('storage', (event) => {
        if (event.key === 'appearance' || event.key === null) {
            current = readSaved();
            applyTheme(current);
            notify();
        }
    });
}
function subscribe(callback: () => void) {
    window.addEventListener(eventName, callback);
    return () => window.removeEventListener(eventName, callback);
}
export function useAppearance() {
    const appearance = useSyncExternalStore(
        subscribe,
        () => current,
        () => 'system' as Appearance,
    );
    function updateAppearance(mode: Appearance) {
        current = mode;
        try {
            localStorage.setItem('appearance', mode);
        } catch {
            /* Keep the session preference when storage is unavailable. */
        }
        applyTheme(mode);
        notify();
    }
    return { appearance, updateAppearance };
}
