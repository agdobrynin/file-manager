import { emitter } from "@/event-bus.js";
import { router, usePage } from "@inertiajs/vue3";
import { onUnmounted, ref, toRaw } from "vue";

export const EVENT_LOAD_FILES_NEXT_PAGE = 'EVENT_LOAD_FILES_NEXT_PAGE';

export function useDoLoadFiles() {
    const page = usePage();
    const filesFetching = ref(false);
    const filesList = ref(page.props.files?.data || []);
    const next = ref(page.props.files?.links?.next || null);
    const filesTotal = ref(page.props.files?.meta?.total || 0);
    
    function filesReset(files) {
        const { data, links, meta } = toRaw(files || {});
        
        filesList.value = data || [];
        next.value = links?.next || null;
        filesTotal.value = meta?.total || 0;
    }
    
    function fetchNextPage() {
        if (next.value && ! filesFetching.value) {
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
