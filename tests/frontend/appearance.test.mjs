import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';
import ts from 'typescript';

function environment(saved = null, osDark = false, blocked = false) {
    const window = new EventTarget();
    const media = new EventTarget();
    media.matches = osDark;
    window.matchMedia = () => media;
    const store = new Map(saved ? [['appearance', saved]] : []);
    const classes = new Set();
    const document = {
        documentElement: {
            classList: {
                toggle(name, on) {
                    if (on) classes.add(name);
                    else classes.delete(name);
                },
            },
            style: {},
        },
    };
    const localStorage = {
        getItem(key) {
            if (blocked) throw Error('Storage blocked');
            return store.get(key) ?? null;
        },
        setItem(key, value) {
            if (blocked) throw Error('Storage blocked');
            store.set(key, value);
        },
    };
    const output = ts.transpileModule(readFileSync(new URL('../../resources/js/hooks/use-appearance.tsx', import.meta.url), 'utf8'), {
        compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 },
    }).outputText;
    const exports = {};
    const subscriptions = [];
    vm.runInNewContext(output, {
        exports,
        Event,
        window,
        document,
        localStorage,
        require: () => ({
            useSyncExternalStore(subscribe, snapshot) {
                subscriptions.push(subscribe(() => {}));
                return snapshot();
            },
        }),
    });
    exports.initializeTheme();
    return { api: exports, window, media, store, document, dark: () => classes.has('dark'), subscriptions };
}

test('saved choices survive loading and remain synchronized across mounted controls', () => {
    const env = environment('dark');
    assert.equal(env.dark(), true);
    const first = env.api.useAppearance();
    const second = env.api.useAppearance();
    assert.equal(first.appearance, 'dark');
    second.updateAppearance('light');
    assert.equal(env.dark(), false);
    assert.equal(env.store.get('appearance'), 'light');
    assert.equal(env.api.useAppearance().appearance, 'light');
    env.subscriptions.forEach((unsubscribe) => unsubscribe());
    env.media.matches = true;
    env.media.dispatchEvent(new Event('change'));
    assert.equal(env.dark(), false);
});

test('system mode tracks OS changes even after controls unmount', () => {
    const env = environment('system');
    env.api.useAppearance();
    env.subscriptions.forEach((unsubscribe) => unsubscribe());
    env.media.matches = true;
    env.media.dispatchEvent(new Event('change'));
    assert.equal(env.dark(), true);
    assert.equal(env.document.documentElement.style.colorScheme, 'dark');
});

test('cross-tab changes and clearing the preference update the active theme', () => {
    const env = environment('light', true);
    const storageEvent = () => {
        const event = new Event('storage');
        Object.defineProperty(event, 'key', { value: 'appearance' });
        env.window.dispatchEvent(event);
    };
    env.store.set('appearance', 'dark');
    storageEvent();
    assert.equal(env.api.useAppearance().appearance, 'dark');
    assert.equal(env.dark(), true);
    env.store.delete('appearance');
    storageEvent();
    assert.equal(env.api.useAppearance().appearance, 'system');
    assert.equal(env.dark(), true);
});

test('invalid preferences use system mode and blocked storage still allows switching', () => {
    const invalid = environment('invalid', true);
    assert.equal(invalid.api.useAppearance().appearance, 'system');
    assert.equal(invalid.dark(), true);
    const blocked = environment(null, false, true);
    blocked.api.useAppearance().updateAppearance('dark');
    assert.equal(blocked.dark(), true);
    assert.equal(blocked.api.useAppearance().appearance, 'dark');
});
