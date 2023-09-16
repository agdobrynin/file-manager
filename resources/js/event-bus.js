import mitt from "mitt";

export const emitter = mitt();

export const SHOW_NOTIFICATION = 'SHOW_SUCCESS_DIALOG';
export const FILES_CHOOSE = 'FILES_CHOOSE';
export const FILES_UPLOADED_SUCCESS = 'FILES_UPLOADED_SUCCESS';
export const FOLDER_CREATE_SUCCESS = 'FOLDER_CREATE_SUCCESS';
export const FILES_UPLOADED_FAILED = 'FILES_UPLOADED_FAILED';

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
