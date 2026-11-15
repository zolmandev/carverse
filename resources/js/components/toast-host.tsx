import { Check, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import './toast-host.css';

type Toast = { type: 'success' | 'error'; message: string };

export default function ToastHost() {
    const [toast, setToast] = useState<Toast | null>(null);

    useEffect(() => {
        const showToast = (event: Event) => setToast((event as CustomEvent<Toast>).detail);
        window.addEventListener('cv:toast', showToast);
        return () => window.removeEventListener('cv:toast', showToast);
    }, []);

    useEffect(() => {
        if (!toast) return;
        const timer = window.setTimeout(() => setToast(null), 4200);
        return () => window.clearTimeout(timer);
    }, [toast]);

    if (!toast) return null;

    return (
        <div className={`cv-toast cv-toast-${toast.type}`} role="status">
            <span className="cv-toast-icon">{toast.type === 'success' ? <Check size={17} /> : <X size={17} />}</span>
            <span>{toast.message}</span>
            <button type="button" aria-label="Dismiss notification" onClick={() => setToast(null)}>
                <X size={14} />
            </button>
        </div>
    );
}
