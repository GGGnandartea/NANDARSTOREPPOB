import React, { useEffect, useRef } from 'react';

let turnstileScriptPromise = null;

function loadTurnstileScript() {
    if (typeof window === 'undefined') return Promise.resolve(null);
    if (window.turnstile) return Promise.resolve(window.turnstile);
    if (turnstileScriptPromise) return turnstileScriptPromise;

    turnstileScriptPromise = new Promise((resolve, reject) => {
        const existing = document.querySelector('script[data-cf-turnstile="true"]');
        if (existing) {
            existing.addEventListener('load', () => resolve(window.turnstile));
            existing.addEventListener('error', reject);
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
        script.async = true;
        script.defer = true;
        script.setAttribute('data-cf-turnstile', 'true');
        script.onload = () => resolve(window.turnstile);
        script.onerror = (err) => {
            turnstileScriptPromise = null;
            reject(err);
        };
        document.head.appendChild(script);
    });

    return turnstileScriptPromise;
}

export default function TurnstileWidget({
    siteKey,
    onVerify,
    resetKey = 0,
    theme = 'dark',
    error = '',
}) {
    const containerRef = useRef(null);
    const widgetIdRef = useRef(null);
    const onVerifyRef = useRef(onVerify);

    useEffect(() => {
        onVerifyRef.current = onVerify;
    }, [onVerify]);

    useEffect(() => {
        if (!siteKey) return;
        let disposed = false;

        loadTurnstileScript()
            .then((turnstile) => {
                if (disposed || !turnstile || !containerRef.current) return;

                if (widgetIdRef.current !== null) {
                    try {
                        turnstile.remove(widgetIdRef.current);
                    } catch (e) {}
                    widgetIdRef.current = null;
                }

                widgetIdRef.current = turnstile.render(containerRef.current, {
                    sitekey: siteKey,
                    theme: theme,
                    size: 'flexible',
                    callback: (token) => {
                        onVerifyRef.current?.(token || '');
                    },
                    'expired-callback': () => {
                        onVerifyRef.current?.('');
                    },
                    'error-callback': () => {
                        onVerifyRef.current?.('');
                    },
                });
            })
            .catch(() => {
                onVerifyRef.current?.('');
            });

        return () => {
            disposed = true;
            if (widgetIdRef.current !== null && window.turnstile) {
                try {
                    window.turnstile.remove(widgetIdRef.current);
                } catch (e) {}
                widgetIdRef.current = null;
            }
        };
    }, [siteKey, theme]);

    useEffect(() => {
        if (resetKey > 0 && widgetIdRef.current !== null && window.turnstile) {
            try {
                window.turnstile.reset(widgetIdRef.current);
                onVerifyRef.current?.('');
            } catch (e) {}
        }
    }, [resetKey]);

    if (!siteKey) return null;

    return (
        <div className="pt-1">
            <div
                ref={containerRef}
                className="w-full min-h-[65px] flex items-center justify-center overflow-hidden rounded-xl"
            />
            {error && (
                <p className="text-red-400 text-[11px] font-bold mt-1.5 px-1">{error}</p>
            )}
        </div>
    );
}
