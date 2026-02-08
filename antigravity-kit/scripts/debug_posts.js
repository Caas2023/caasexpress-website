require('dotenv').config();
const db = require('./src/models/database');

async function checkPosts() {
    try {
        await db.initDatabase(); // Initialize DB first
        console.log('Fetching all posts...');
        // Pass empty object to ignore filters and get EVERYTHING
        const posts = await db.posts.getAll({});

        console.log(`Found ${posts.length} posts.`);

        if (posts.length > 0) {
            console.log('Sample Post Statuses:');
            posts.forEach(p => {
                console.log(`ID: ${p.id} | Status: '${p.status}' | Type: '${p.type}' | Date: ${p.date}`);
            });
        }
    } catch (error) {
        console.error('Error checking posts:', error);
    }
}

checkPosts();
