import { afterEach, beforeEach, describe, expect, jest, test } from '@jest/globals';

const mockGetSetting = jest.fn();
const mockExpireStaleSessions = jest.fn();
const mockLogger = {
  info: jest.fn(),
  debug: jest.fn(),
  warn: jest.fn(),
  error: jest.fn(),
};

jest.unstable_mockModule('../src/db.js', () => ({
  getSetting: mockGetSetting,
  expireStaleSessions: mockExpireStaleSessions,
}));

jest.unstable_mockModule('../src/utils/logger.js', () => ({
  logger: mockLogger,
}));

const { runExpireJob, startScheduler, stopScheduler } = await import('../src/utils/scheduler.js');

describe('scheduler', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    jest.useFakeTimers();
    stopScheduler();
  });

  afterEach(() => {
    stopScheduler();
    jest.useRealTimers();
  });

  test('runExpireJob return expired count', async () => {
    mockExpireStaleSessions.mockResolvedValueOnce(2);
    await expect(runExpireJob()).resolves.toBe(2);
    expect(mockLogger.info).toHaveBeenCalledWith(
      expect.objectContaining({ expired: 2 }),
      expect.any(String)
    );
  });

  test('runExpireJob handle error dan return 0', async () => {
    mockExpireStaleSessions.mockRejectedValueOnce(new Error('db fail'));
    await expect(runExpireJob()).resolves.toBe(0);
    expect(mockLogger.error).toHaveBeenCalled();
  });

  test('startScheduler jalankan job langsung + interval', async () => {
    mockGetSetting.mockResolvedValueOnce('1');
    mockExpireStaleSessions.mockResolvedValue(1);

    await startScheduler();
    expect(mockExpireStaleSessions).toHaveBeenCalledTimes(1);

    await jest.advanceTimersByTimeAsync(60000);
    expect(mockExpireStaleSessions).toHaveBeenCalledTimes(2);
  });

  test('startScheduler tidak start ulang jika sudah berjalan', async () => {
    mockGetSetting.mockResolvedValueOnce('1');
    mockExpireStaleSessions.mockResolvedValue(0);

    await startScheduler();
    await startScheduler();

    expect(mockGetSetting).toHaveBeenCalledTimes(1);
    expect(mockLogger.warn).toHaveBeenCalled();
    stopScheduler();
  });

  test('stopScheduler menghentikan interval', async () => {
    mockGetSetting.mockResolvedValueOnce('1');
    mockExpireStaleSessions.mockResolvedValue(1);

    await startScheduler();
    stopScheduler();
    await jest.advanceTimersByTimeAsync(60000);

    expect(mockExpireStaleSessions).toHaveBeenCalledTimes(1);
  });
});
