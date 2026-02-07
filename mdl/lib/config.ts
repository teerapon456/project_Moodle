export const moodleConfig = {
  publicUrl: process.env.NEXT_PUBLIC_MOODLE_PUBLIC_URL || process.env.MOODLE_PUBLIC_URL || 'http://localhost:8080',
  internalUrl: process.env.MOODLE_INTERNAL_URL || 'http://moodle',
  adminToken: process.env.MOODLE_ADMIN_TOKEN || '',
  wsToken: process.env.MOODLE_WS_TOKEN || '',
  service: process.env.MOODLE_SERVICE_NAME || 'moodle_mobile_app',
  format: 'json'
};
