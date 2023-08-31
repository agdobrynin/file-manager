import mitt from "mitt";

export const emitter = mitt();

export const SHOW_NOTIFICATION = 'SHOW_SUCCESS_DIALOG';

export const successMessage = (message) => emitter.emit(SHOW_NOTIFICATION, { type: 'success', message });
