/**
 *
 * @param {number} bytes
 * @param {number} factorDigits
 * @return {string}
 */
export function bytesToSize(bytes, factorDigits = 2) {
    const units = [ 'byte', 'kB', 'Mb', 'Gb', 'Tb' ];
    
    const unitIndex = Math.max(0, Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1));
    
    return `${(bytes / (1024 ** unitIndex)).toFixed(factorDigits)} ${units[unitIndex]}`;
}

/**
 * @param {Function} fn
 * @param {Number} wait
 * @return {(function(...[*]): void)|*}
 */
export function debounce(fn, wait) {
    let timer;
    
    return function (...args) {
        if (timer) {
            clearTimeout(timer);
        }
        
        const context = this;
        
        timer = setTimeout(() => {
            fn.apply(context, args);
        }, wait);
    }
}

export function fixWindowHistory(queryParamKeys = [ 'page' ]) {
    const params = new URLSearchParams((new URL(window.location.href)).search);

    queryParamKeys.forEach((key) => params.delete(key));

    const queryString = params.toString();

    window.history.replaceState(
        {},
        '',
        `${window.location.pathname}${queryString ? '?' + queryString : ''}${window.location.hash}`,
    )
}
