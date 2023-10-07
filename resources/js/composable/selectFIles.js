import { computed, ref } from "vue";

export function useSelectFiles() {
    const selectedFileIds = ref([]);
    const selectAllFiles = ref(false);
    
    const clearSelectedFiles = () => {
        selectedFileIds.value = [];
        selectAllFiles.value = false;
    };
    
    const paramsAllAndIds = computed(() => {
        if (selectAllFiles.value || selectedFileIds.value.length) {
            return {
                all: selectAllFiles.value,
                ids: selectAllFiles.value ? [] : selectedFileIds.value,
            }
        }
        
        return {};
    });
    
    return {
        selectedFileIds,
        selectAllFiles,
        paramsAllAndIds,
        clearSelectedFiles,
    }
}
