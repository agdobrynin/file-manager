import mitt from "mitt";

export const emitter = mitt();

export const SHOW_NOTIFICATION = 'SHOW_SUCCESS_DIALOG';
export const FILES_CHOOSE = 'FILES_CHOOSE';

/**
 * Success notification.
 *
 * @param {string|string[]} message
 * @param {number} timeout
 */
export const successMessage = (message, timeout = 6000) => emitter.emit(SHOW_NOTIFICATION, {
    type: 'success', message, timeout
});

/**
 * Error notification.
 *
 * @param {string|string[]} message
 * @param {number} timeout
 */
export const errorMessage = (message, timeout = 10000) => emitter.emit(SHOW_NOTIFICATION, {
    type: 'error', message, timeout
});
