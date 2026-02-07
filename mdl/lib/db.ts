import mysql from 'mysql2/promise';

// Portal Database Connection (for Authentication)
export const portalDb = mysql.createPool({
    host: process.env.DB_HOST || 'db',
    user: process.env.DB_USER || 'myhr_user',
    password: process.env.DB_PASS || 'MyHR_S3cur3_P@ss_2026!',
    database: process.env.DB_NAME || 'myhr_portal',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
});

// Moodle Database Connection (for Token Generation)
export const moodleDb = mysql.createPool({
    host: process.env.MOODLE_DB_HOST || 'moodle_db',
    user: process.env.MOODLE_DB_USER || 'moodle_user',
    password: process.env.MOODLE_DB_PASS || 'Moodle_S3cur3_P@ss_2026!',
    database: process.env.MOODLE_DB_NAME || 'moodle',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
});
