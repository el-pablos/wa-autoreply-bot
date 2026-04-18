import { describe, expect, jest, test } from '@jest/globals';
import {
  calculateTypingMs,
  simulateTyping,
} from '../../src/utils/humanTyping.js';

describe('humanTyping.calculateTypingMs', () => {
  test('menghasilkan durasi proporsional dengan clamp min/max', () => {
    const ms = calculateTypingMs(10, {
      msPerChar: 20,
      min: 100,
      max: 1000,
      jitter: 0,
    });
    expect(ms).toBe(200);

    const minMs = calculateTypingMs(0, {
      msPerChar: 20,
      min: 100,
      max: 1000,
      jitter: 0,
    });
    expect(minMs).toBe(100);

    const maxMs = calculateTypingMs(999, {
      msPerChar: 20,
      min: 100,
      max: 1000,
      jitter: 0,
    });
    expect(maxMs).toBe(1000);
  });

  test('jitter bisa dipastikan dengan jitterFn', () => {
    // jitterFn=1 -> faktor 1 + (1*2-1)*0.5 = 1.5
    const ms = calculateTypingMs(10, {
      msPerChar: 20,
      min: 1,
      max: 1000,
      jitter: 0.5,
      jitterFn: () => 1,
    });

    expect(ms).toBe(300);
  });
});

describe('humanTyping.simulateTyping', () => {
  test('kirim composing -> paused dengan jeda', async () => {
    const updates = [];
    const sock = {
      sendPresenceUpdate: jest.fn(async (state, jid) => {
        updates.push(`${state}:${jid}`);
      }),
    };
    const sleepFn = jest.fn(async () => {});

    await simulateTyping(sock, '6281@s.whatsapp.net', 800, { sleepFn });

    expect(sock.sendPresenceUpdate).toHaveBeenCalledTimes(2);
    expect(sleepFn).toHaveBeenCalledWith(800);
    expect(updates).toEqual([
      'composing:6281@s.whatsapp.net',
      'paused:6281@s.whatsapp.net',
    ]);
  });

  test('aman saat sock tidak menyediakan sendPresenceUpdate', async () => {
    await expect(simulateTyping({}, '6281@s.whatsapp.net', 500)).resolves.toBeUndefined();
  });
});
