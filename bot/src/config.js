import 'dotenv/config';

export const config = {
  db: {
    host:     process.env.DB_HOST     || 'localhost',
    port:     parseInt(process.env.DB_PORT || '3306', 10),
    user:     process.env.DB_USER     || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME     || 'wabot',
    waitForConnections: true,
    connectionLimit:    10,
    queueLimit:         0,
    charset:            'utf8mb4',
  },
  bot: {
    port:     parseInt(process.env.BOT_PORT || '3001', 10),
    authDir:  './auth_info',
    logLevel: process.env.LOG_LEVEL || 'info',
  },
  env: process.env.NODE_ENV || 'development',
};
