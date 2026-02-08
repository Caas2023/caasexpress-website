/**
 * Media Routes
 */

const express = require('express');
const router = express.Router();
const mediaController = require('../controllers/media.controller');

module.exports = (authenticate, upload, UPLOADS_DIR) => {
    router.get('/', authenticate, mediaController.list);
    router.get('/:id', authenticate, mediaController.get);
    router.post('/', authenticate, upload.single('file'), mediaController.upload);
    router.post('/', authenticate, mediaController.uploadBinary(UPLOADS_DIR));
    router.post('/:id', authenticate, mediaController.update);

    return router;
};
