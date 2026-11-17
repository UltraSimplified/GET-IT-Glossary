import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted } from 'vue';

/**
 * Ask before leaving a form with unsaved changes, whether by following a
 * link inside the site or by closing or reloading the tab.
 */
export function useLeaveGuard(
    isDirty: () => boolean,
    message = 'Leave this page? Your changes have not been saved.',
) {
    let removeListener: (() => void) | undefined;

    function onBeforeUnload(event: BeforeUnloadEvent) {
        if (isDirty()) {
            event.preventDefault();
        }
    }

    onMounted(() => {
        removeListener = router.on('before', (event) => {
            // Saving the form is a visit too; only guard plain navigation.
            if (event.detail.visit.method !== 'get') {
                return;
            }

            if (isDirty() && !window.confirm(message)) {
                event.preventDefault();
            }
        });

        window.addEventListener('beforeunload', onBeforeUnload);
    });

    onBeforeUnmount(() => {
        removeListener?.();
        window.removeEventListener('beforeunload', onBeforeUnload);
    });
}
