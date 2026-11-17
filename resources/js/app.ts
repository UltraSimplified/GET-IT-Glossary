import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

// The server writes the site's name onto <html>; a rename reloads the page.
function siteName(): string {
    return document.documentElement.dataset.siteName || 'Glossary';
}

void createInertiaApp({
    title: (title) => (title ? `${title} | ${siteName()}` : siteName()),
    // SiteLayout is persistent: it stays mounted while pages change inside it.
    layout: (name) => {
        switch (true) {
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [SiteLayout, SettingsLayout];
            default:
                return SiteLayout;
        }
    },
    withApp: (app) => {
        app.directive('focus', {
            mounted: (el: HTMLElement, shouldFocus) => {
                if (shouldFocus.value !== false) {
                    el.focus();
                }
            },
        });
    },
    progress: {
        // The theme's accent colour, written into the page head.
        color:
            getComputedStyle(document.documentElement)
                .getPropertyValue('--theme-accent')
                .trim() || '#1f9aa1',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
