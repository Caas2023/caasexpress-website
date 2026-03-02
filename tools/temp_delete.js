const fs = require('fs');
const path = require('path');

const targetDir = path.join(__dirname, 'api', 'src');

try {
    if (fs.existsSync(targetDir)) {
        fs.rmSync(targetDir, { recursive: true, force: true });
        console.log(`Directory ${targetDir} successfully deleted.`);
    } else {
        console.log(`Directory ${targetDir} does not exist.`);
    }
} catch (error) {
    console.error(`Error deleting directory: ${error.message}`);
}
