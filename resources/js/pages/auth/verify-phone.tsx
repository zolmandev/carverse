import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, MessageSquareText } from 'lucide-react';
import { FormEventHandler } from 'react';
import { useState } from 'react';

export default function VerifyPhone({ phone, hasPhone, status }: { phone: string; hasPhone: boolean; status?: string }) {
    const verification = useForm({ code: '' });
    const resend = useForm<{ phone?: string }>({});
    const number = useForm({ phone: '' });
    const [editingNumber, setEditingNumber] = useState(!hasPhone);

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        verification.post(route('phone.verification.verify'));
    };

    return (
        <AuthLayout title="Verify your mobile" description={`Enter the 6-digit code sent to ${phone}.`}>
            <Head title="Phone verification" />

            {status === 'verification-code-sent' && (
                <div className="mb-5 flex items-center gap-2 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-sm text-emerald-700 dark:text-emerald-300">
                    <MessageSquareText size={17} /> A fresh verification code is on its way.
                </div>
            )}

            {editingNumber ? (
                <form
                    className="mb-6 space-y-4 rounded-xl border p-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        number.post(route('phone.verification.number'), { onSuccess: () => setEditingNumber(false) });
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="phone">Kenyan mobile number</Label>
                        <Input
                            id="phone"
                            type="tel"
                            autoComplete="tel"
                            required
                            autoFocus={!hasPhone}
                            value={number.data.phone}
                            onChange={(event) => number.setData('phone', event.target.value)}
                            placeholder="0712 345 678"
                        />
                        <InputError message={number.errors.phone} />
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={number.processing}>
                            {number.processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            Save and send code
                        </Button>
                        {hasPhone && <Button type="button" variant="secondary" onClick={() => setEditingNumber(false)}>Cancel</Button>}
                    </div>
                </form>
            ) : (
                <form onSubmit={submit} className="space-y-5">
                <div className="grid gap-2">
                    <Label htmlFor="code">Verification code</Label>
                    <Input
                        id="code"
                        inputMode="numeric"
                        autoComplete="one-time-code"
                        pattern="[0-9]{6}"
                        maxLength={6}
                        required
                        autoFocus
                        className="text-center text-xl tracking-[0.35em]"
                        value={verification.data.code}
                        onChange={(event) => verification.setData('code', event.target.value.replace(/\D/g, '').slice(0, 6))}
                        placeholder="000000"
                    />
                    <InputError message={verification.errors.code} />
                </div>

                <Button className="w-full" disabled={verification.processing || verification.data.code.length !== 6}>
                    {verification.processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                    Verify and continue
                </Button>
                </form>
            )}

            <div className="mt-6 flex flex-col items-center gap-3 text-sm">
                {hasPhone && !editingNumber && (
                    <>
                        <button
                            type="button"
                            disabled={resend.processing}
                            onClick={() => resend.post(route('phone.verification.send'), { preserveScroll: true })}
                            className="text-muted-foreground underline underline-offset-4 disabled:opacity-50"
                        >
                            {resend.processing ? 'Sending…' : 'Send another code'}
                        </button>
                        <InputError message={resend.errors.phone} />
                        <button type="button" onClick={() => setEditingNumber(true)} className="text-muted-foreground underline underline-offset-4">
                            Use a different number
                        </button>
                    </>
                )}
                <TextLink href={route('logout')} method="post">Log out</TextLink>
            </div>
        </AuthLayout>
    );
}
