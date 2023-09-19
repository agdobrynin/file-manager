/**
 *
 * @param {number} bytes
 * @param {number} factorDigits
 * @return {string}
 */
export function bytesToSize(bytes, factorDigits = 2) {
    const units = ['byte', 'kB', 'Mb', 'Gb', 'Tb'];

    const unitIndex = Math.max(0, Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1));

    return `${(bytes / (1024 ** unitIndex)).toFixed(factorDigits)} ${units[unitIndex]}`;
}
