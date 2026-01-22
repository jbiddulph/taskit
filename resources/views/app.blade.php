<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes, viewport-fit=cover">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="ZapTask">
        <meta name="theme-color" content="#3B82F6">
        <meta name="format-detection" content="telephone=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <title inertia>ZapTask</title>

        <link rel="icon" href="/favicon.ico" type="image/x-icon">
        <link rel="apple-touch-icon" href="/zap_icon.png">
        
        <!-- PWA Support -->
        <link rel="manifest" href="/manifest.json">
        <meta name="apple-mobile-web-app-title" content="ZapTask">
        <link rel="apple-touch-icon" sizes="152x152" href="/zap_icon.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/zap_icon.png">
        <link rel="apple-touch-icon" sizes="167x167" href="/zap_icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <style>
            .cookie-consent {
                position: fixed;
                inset: auto 1.5rem 1.5rem 1.5rem;
                display: none;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                padding: 1rem 1.25rem;
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 0.75rem;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
                z-index: 1000;
            }

            .cookie-consent__text {
                font-size: 0.95rem;
                color: #1f2937;
            }

            .cookie-consent__actions {
                display: flex;
                gap: 0.5rem;
                flex-shrink: 0;
            }

            .cookie-consent__button {
                border-radius: 0.5rem;
                padding: 0.45rem 0.9rem;
                font-size: 0.9rem;
                font-weight: 600;
                border: 1px solid transparent;
                cursor: pointer;
            }

            .cookie-consent__button--accept {
                background: #2563eb;
                color: #ffffff;
            }

            .cookie-consent__button--decline {
                background: #f9fafb;
                color: #111827;
                border-color: #d1d5db;
            }

            @media (max-width: 640px) {
                .cookie-consent {
                    inset: auto 1rem 1rem 1rem;
                    flex-direction: column;
                    align-items: flex-start;
                }

                .cookie-consent__actions {
                    width: 100%;
                }

                .cookie-consent__button {
                    width: 100%;
                }
            }
        </style>

        <script>
            (function() {
                const CONSENT_KEY = 'analytics_consent';
                let storedConsent = null;

                try {
                    storedConsent = localStorage.getItem(CONSENT_KEY);
                } catch (error) {
                    storedConsent = null;
                }

                window.__analyticsConsent = storedConsent;

                const enableAnalytics = () => {
                    if (window.__analyticsEnabled) return;
                    window.__analyticsEnabled = true;

                    window.dataLayer = window.dataLayer || [];
                    window.gtag = window.gtag || function() { window.dataLayer.push(arguments); };
                    window.gtag('js', new Date());
                    window.gtag('config', 'G-Z1ZXQ6Z1M9');

                    const script = document.createElement('script');
                    script.async = true;
                    script.src = 'https://www.googletagmanager.com/gtag/js?id=G-Z1ZXQ6Z1M9';
                    document.head.appendChild(script);
                };

                if (storedConsent === 'accepted') {
                    enableAnalytics();
                }

                window.__enableAnalytics = enableAnalytics;

                document.addEventListener('DOMContentLoaded', function() {
                    const banner = document.getElementById('cookie-consent-banner');
                    if (!banner) return;

                    if (storedConsent === 'accepted' || storedConsent === 'declined') {
                        return;
                    }

                    banner.style.display = 'flex';

                    const acceptButton = document.getElementById('cookie-consent-accept');
                    const declineButton = document.getElementById('cookie-consent-decline');

                    if (acceptButton) {
                        acceptButton.addEventListener('click', function() {
                            window.__analyticsConsent = 'accepted';
                            try {
                                localStorage.setItem(CONSENT_KEY, 'accepted');
                            } catch (error) {}
                            enableAnalytics();
                            banner.style.display = 'none';
                        });
                    }

                    if (declineButton) {
                        declineButton.addEventListener('click', function() {
                            window.__analyticsConsent = 'declined';
                            try {
                                localStorage.setItem(CONSENT_KEY, 'declined');
                            } catch (error) {}
                            banner.style.display = 'none';
                        });
                    }
                });
            })();
        </script>

        @php
            $appAsset = \App\Helpers\RelativeViteHelper::asset('resources/js/app.ts');
            $pageAsset = \App\Helpers\RelativeViteHelper::asset("resources/js/pages/{$page['component']}.vue");
            $appCss = \App\Helpers\RelativeViteHelper::css('resources/js/app.ts');
        @endphp
        
        @if($appCss)
            <link rel="stylesheet" href="{{ $appCss }}">
        @endif
        
        <script type="module" src="{{ $appAsset }}"></script>
        <script type="module" src="{{ $pageAsset }}"></script>
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        <script>
            window.VITE_SUPABASE_URL = "{{ env('VITE_SUPABASE_URL') }}";
            window.VITE_SUPABASE_ANON_KEY = "{{ env('VITE_SUPABASE_ANON_KEY') }}";
            
            // Mobile debugging - log errors to help diagnose issues
            window.addEventListener('error', function(e) {
                console.error('JavaScript Error:', e.error, e.filename, e.lineno);
            });
            
            window.addEventListener('unhandledrejection', function(e) {
                console.error('Unhandled Promise Rejection:', e.reason);
            });
            
            // Log device info for debugging
            console.log('Device Info:', {
                userAgent: navigator.userAgent,
                viewport: {
                    width: window.innerWidth,
                    height: window.innerHeight
                },
                pixelRatio: window.devicePixelRatio
            });
            
            // Register Service Worker for PWA
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register('/sw.js')
                        .then(function(registration) {
                            console.log('ServiceWorker registration successful');
                        })
                        .catch(function(err) {
                            console.log('ServiceWorker registration failed: ', err);
                        });
                });
            }
        </script>
        @inertia
        <div
            id="cookie-consent-banner"
            class="cookie-consent"
            role="dialog"
            aria-live="polite"
            aria-label="Cookie consent"
        >
            <div class="cookie-consent__text">
                We use cookies and analytics to understand usage and improve ZapTask. Read our
                <a href="/cookies" class="underline font-medium">cookie policy</a> or choose your preference.
            </div>
            <div class="cookie-consent__actions">
                <button id="cookie-consent-accept" class="cookie-consent__button cookie-consent__button--accept" type="button">
                    Accept
                </button>
                <button id="cookie-consent-decline" class="cookie-consent__button cookie-consent__button--decline" type="button">
                    Decline
                </button>
            </div>
        </div>
        <div id="notification-container"></div>
    </body>
</html>
