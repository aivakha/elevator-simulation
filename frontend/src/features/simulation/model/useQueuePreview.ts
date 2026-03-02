import { useCallback, useEffect, useMemo, useState } from 'react';
import { fetchQueuePreview } from '../api/simulationApi';
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

  const refresh = useCallback(async (options?: { force?: boolean }) => {
    if (simulationId === '') {
      return;
    }

    try {
      const result = await fetchQueuePreview(simulationId);
      setPreview((current) => {
        if (options?.force === true) {
          return result;
        }

        if (result.simulationId !== simulationId) {
          return current;
        }

        if (current.simulationId === simulationId && result.tickNumber < current.tickNumber) {
          return current;
        }

        return result;
      });
    } catch {
      // Keep current data while backend is unavailable.
    }
  }, [simulationId]);

  useEffect(() => {
    void refresh();
  }, [refresh]);

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
              if (current.simulationId === simulationId && runtimePayload.tickNumber <= current.tickNumber) {
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
    () => ({
      preview,
      isLive,
      refresh,
    }),
    [preview, isLive, refresh],
  );
}
