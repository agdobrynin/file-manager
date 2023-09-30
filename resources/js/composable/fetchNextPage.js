import { onUnmounted, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { emitter } from "@/event-bus.js";

export const EVENT_LOAD_FILES_NEXT_PAGE = 'EVENT_LOAD_FILES_NEXT_PAGE';

export function useDoLoadFiles(initFiles) {
    const filesFetching = ref(false);
    const filesList = ref(initFiles.data || []);
    const next = ref(initFiles.links?.next || null);
    const filesTotal = ref(initFiles?.meta?.total || 0);
    const initUrl = usePage().url;
    
    function filesReset(files) {
        filesList.value = files?.data || [];
        next.value = files?.links?.next || null;
        filesTotal.value = files?.meta?.total || 0;
    }

    function fetchNextPage() {
        if (next.value && !filesFetching.value) {
            router.visit(String(next.value),
                {
                    preserveState: true,
                    only: [ 'files' ],
                    onStart: function () {
                        filesFetching.value = true;
                    },
                    onFinish: function () {
                        filesFetching.value = false;
                    },
                    onSuccess: ({ props: { files: { data = [], links } } }) => {
                        filesList.value = [ ...filesList.value, ...data ];
                        next.value = links?.next || null;
                        window.history.replaceState({}, usePage().url, initUrl);
                    },
                })
        }
    }
    
    emitter.on(EVENT_LOAD_FILES_NEXT_PAGE, fetchNextPage);
    
    onUnmounted(() => {
        filesReset();
    });
    
    return {
        filesFetching,
        filesList,
        filesTotal,
        filesReset,
    }
}
