import { useEffect, useMemo, useState } from 'react';
import type { QueuePreview } from './types';

const emptyQueuePreview: QueuePreview = {
  simulationId: '',
  tickNumber: 0,
  isEmergencyMode: false,
  tickIntervalMs: 1000,
  floorTravelSeconds: 1,
  maxPendingCalls: 1,
  waitingPassengers: 0,
  pickedUpPassengers: 0,
  droppedOffPassengers: 0,
  totalPassengers: 0,
  elevators: [],
  pendingHallCalls: [],
};

export function useQueuePreview(simulationId: string) {
  const [preview, setPreview] = useState<QueuePreview>(emptyQueuePreview);
  const [isLive, setIsLive] = useState(false);

  // Clear stale data immediately when switching simulations.
  useEffect(() => {
    setPreview(emptyQueuePreview);
  }, [simulationId]);

  useEffect(() => {
    if (simulationId === '') {
      return;
    }

    const wsProtocol = window.location.protocol === 'https:' ? 'wss' : 'ws';
    const wsUrl = `${wsProtocol}://${window.location.host}/ws`;
    let socket: WebSocket | null = null;
    let reconnectTimer: number | null = null;
    let active = true;

    const connect = () => {
      socket = new WebSocket(wsUrl);

      socket.addEventListener('open', () => {
        setIsLive(true);
        // Request the latest snapshot for this simulation immediately on connect.
        socket?.send(JSON.stringify({ type: 'subscribe', simulationId }));
      });

      socket.addEventListener('message', (event) => {
        try {
          const message = JSON.parse(event.data) as {
            payload?: QueuePreview;
            data?: QueuePreview;
            simulationId?: string;
          };
          const runtimePayload = (message.payload ?? message.data ?? message) as QueuePreview;

          if (runtimePayload && runtimePayload.simulationId === simulationId) {
            setPreview((current) => {
              // tickNumber === 0 means a reset — always apply it regardless of
              // the current tick so the view snaps back to the initial state.
              const isReset = runtimePayload.tickNumber === 0;
              if (!isReset && current.simulationId === simulationId && runtimePayload.tickNumber <= current.tickNumber) {
                return current;
              }

              return runtimePayload;
            });
          }
        } catch {
          // Ignore malformed message.
        }
      });

      socket.addEventListener('close', () => {
        setIsLive(false);

        if (!active) {
          return;
        }

        reconnectTimer = window.setTimeout(() => {
          connect();
        }, 1500);
      });
    };

    connect();

    return () => {
      active = false;
      setIsLive(false);

      if (reconnectTimer !== null) {
        window.clearTimeout(reconnectTimer);
      }

      socket?.close();
    };
  }, [simulationId]);

  return useMemo(
    () => ({ preview, isLive }),
    [preview, isLive],
  );
}
