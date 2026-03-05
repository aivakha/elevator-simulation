import process from 'node:process';
import Redis from 'ioredis';
import { WebSocketServer } from 'ws';

const websocketPort = Number(process.env.PORT ?? process.env.WS_PORT ?? 8090);
const redisHost = process.env.REDIS_HOST ?? 'redis';
const redisPort = Number(process.env.REDIS_PORT ?? 6379);
const redisPassword = process.env.REDIS_PASSWORD;
const redisUrl = process.env.REDIS_URL;
const redisChannelPatterns = ['simulation:*:ticks', '*simulation:*:ticks'];

const redisOptions = redisUrl
  ? { url: redisUrl }
  : {
      host: redisHost,
      port: redisPort,
      password: redisPassword && redisPassword !== '' ? redisPassword : undefined,
    };

const publisher = new Redis(redisOptions);
const subscriber = new Redis(redisOptions);

const wss = new WebSocketServer({ port: websocketPort });

wss.on('connection', (client) => {
  client.send(JSON.stringify({ type: 'connection', message: 'connected to elevator_v2 websocket' }));

  client.on('message', async (data) => {
    try {
      const msg = JSON.parse(data.toString());

      if (msg.type === 'subscribe' && typeof msg.simulationId === 'string') {
        const historyKey = `simulation:${msg.simulationId}:ticks`;
        const latest = await publisher.lindex(historyKey, 0);

        if (latest && client.readyState === 1) {
          client.send(JSON.stringify({
            channel: `simulation:${msg.simulationId}:ticks`,
            payload: safeJson(latest),
          }));
        }
      }
    } catch {
      // ignore malformed messages
    }
  });
});

subscriber.psubscribe(...redisChannelPatterns, (error) => {
  if (error) {
    console.error('failed to subscribe redis patterns', error);
  }
});

publisher.on('error', (error) => {
  console.error('redis publisher error', error);
});

subscriber.on('error', (error) => {
  console.error('redis subscriber error', error);
});

subscriber.on('pmessage', (_pattern, channel, payload) => {
  const envelope = JSON.stringify({ channel, payload: safeJson(payload) });

  for (const client of wss.clients) {
    if (client.readyState === 1) {
      client.send(envelope);
    }
  }
});

function safeJson(value) {
  try {
    return JSON.parse(value);
  } catch {
    return { raw: value };
  }
}

process.on('SIGTERM', shutdown);
process.on('SIGINT', shutdown);

async function shutdown() {
  await subscriber.quit();
  await publisher.quit();
  wss.close(() => {
    process.exit(0);
  });
}

console.log(`websocket server started on port ${websocketPort}`);
