/**
 * Categories Routes
 */

const express = require('express');
const router = express.Router();
const categoriesController = require('../controllers/js/categories.controller');

module.exports = (authenticate) => {
    router.get('/', authenticate, categoriesController.list);
    router.post('/', authenticate, categoriesController.create);

    return router;
};
