import { describe, expect, jest, test } from '@jest/globals';
import {
  EVENT_MESSAGE_RECEIVED,
  EVENT_REPLY_SENT,
  eventBus,
  publishMessageReceived,
  publishReplySent,
  subscribeSSE,
} from '../../src/utils/eventBus.js';

describe('eventBus publish helpers', () => {
  test('publishMessageReceived emit payload standar', async () => {
    const handler = jest.fn();
    eventBus.once(EVENT_MESSAGE_RECEIVED, handler);

    publishMessageReceived({ phoneNumber: '6281', messageText: 'halo' });

    expect(handler).toHaveBeenCalledWith(
      expect.objectContaining({
        type: EVENT_MESSAGE_RECEIVED,
        phoneNumber: '6281',
        messageText: 'halo',
      }),
    );
  });

  test('publishReplySent emit payload standar', async () => {
    const handler = jest.fn();
    eventBus.once(EVENT_REPLY_SENT, handler);

    publishReplySent({ to: '6281', replyText: 'ok' });

    expect(handler).toHaveBeenCalledWith(
      expect.objectContaining({
        type: EVENT_REPLY_SENT,
        to: '6281',
        replyText: 'ok',
      }),
    );
  });
});

describe('eventBus.subscribeSSE', () => {
  test('callback menerima event + unsubscribe menghentikan stream', () => {
    const callback = jest.fn();
    const unsubscribe = subscribeSSE(callback);

    publishMessageReceived({ phoneNumber: '6281' });
    expect(callback).toHaveBeenCalled();

    callback.mockClear();
    unsubscribe();

    publishMessageReceived({ phoneNumber: '6281' });
    expect(callback).not.toHaveBeenCalled();
  });
});
