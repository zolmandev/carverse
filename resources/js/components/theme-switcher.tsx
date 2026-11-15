import { Appearance, useAppearance } from '@/hooks/use-appearance';
import { Monitor, Moon, Sun } from 'lucide-react';

export default function ThemeSwitcher() {
    const { appearance, updateAppearance } = useAppearance();
    const Icon = appearance === 'dark' ? Moon : appearance === 'light' ? Sun : Monitor;
    return (
        <label className="cv-theme-switcher">
            <Icon size={16} aria-hidden="true" />
            <span className="sr-only">Color theme</span>
            <select aria-label="Color theme" value={appearance} onChange={(event) => updateAppearance(event.target.value as Appearance)}>
                <option value="light">Light</option>
                <option value="dark">Dark</option>
                <option value="system">System</option>
            </select>
        </label>
    );
}
