export const isPdf = (mime) => [
    'application/pdf',
    'application/x-pdf',
    'application/acrobat',
    'application/vnd.pdf',
    'text/pdf',
    'text/x-pdf',
].includes(mime.toLowerCase());

export const isAudio = (mime) => [
    'audio/mpeg',
    'audio/ogg',
    'audio/wav',
    'audio/x-m4a',
    'audio/webm',
].includes(mime.toLowerCase());

export const isVideo = (mime) => [
    'video/mp4',
    'video/mpeg',
    'video/ogg',
    'video/quicktime',
    'video/webm',
].includes(mime.toLowerCase());

export const isDoc = (mime) => [
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-word.document.macroEnabled.12',
    'application/vnd.ms-word.template.macroEnabled.12',
].includes(mime.toLowerCase());

export const isCalc = (mime) => [
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-excel.sheet.macroEnabled.12',
    'application/vnd.ms-excel.template.macroEnabled.12',
].includes(mime.toLowerCase());

export const isArchive = (mime) =>[
    'application/zip',
].includes(mime.toLowerCase());

export const isText = (mime) => [
    'text/plain',
    'text/html',
    'text/css',
    'text/javascript',
    'text/csv',
].includes(mime.toLowerCase());

export const isImage = (mime) => /^image\//.test(mime.toLowerCase());
