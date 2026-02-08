/**
 * Posts Routes
 */

const express = require('express');
const router = express.Router();
const postsController = require('../controllers/posts.controller');

module.exports = (authenticate) => {
    router.get('/', authenticate, postsController.list);
    router.get('/:id', authenticate, postsController.get);
    router.post('/', authenticate, postsController.create);
    router.put('/:id', authenticate, postsController.update);
    router.post('/:id', authenticate, postsController.update);
    router.delete('/:id', authenticate, postsController.remove);

    return router;
};
