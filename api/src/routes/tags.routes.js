/**
 * Tags Routes
 */

const express = require('express');
const router = express.Router();
const tagsController = require('../controllers/tags.controller');

module.exports = (authenticate) => {
    router.get('/', authenticate, tagsController.list);
    router.post('/', authenticate, tagsController.create);

    return router;
};
