import { config } from '../src/config.js';

describe('config', () => {
  test('config.db harus punya semua field wajib', () => {
    expect(config.db).toHaveProperty('host');
    expect(config.db).toHaveProperty('port');
    expect(config.db).toHaveProperty('user');
    expect(config.db).toHaveProperty('database');
    expect(typeof config.db.port).toBe('number');
    expect(config.db.connectionLimit).toBeGreaterThan(0);
  });

  test('config.bot harus punya port dan authDir', () => {
    expect(config.bot).toHaveProperty('port');
    expect(config.bot).toHaveProperty('authDir');
    expect(typeof config.bot.port).toBe('number');
    expect(config.bot.port).toBeGreaterThan(0);
  });

  test('config.env harus string', () => {
    expect(typeof config.env).toBe('string');
  });
});
