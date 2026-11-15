import { useAppearance } from '@/hooks/use-appearance';
import { Moon, Sun } from 'lucide-react';

export default function ThemeSwitcher() {
    const { appearance, updateAppearance } = useAppearance();
    const isDark = appearance === 'dark';
    return (
        <button
            type="button"
            className={`cv-theme-switcher${isDark ? ' is-on' : ''}`}
            role="switch"
            aria-checked={isDark}
            aria-label={isDark ? 'Turn dark theme off' : 'Turn dark theme on'}
            onClick={() => updateAppearance(isDark ? 'light' : 'dark')}
        >
            {isDark ? <Moon size={15} aria-hidden="true" /> : <Sun size={15} aria-hidden="true" />}
            <span>{isDark ? 'ON' : 'OFF'}</span>
            <span className="cv-theme-switcher-track" aria-hidden="true">
                <span className="cv-theme-switcher-thumb" />
            </span>
        </button>
    );
}
