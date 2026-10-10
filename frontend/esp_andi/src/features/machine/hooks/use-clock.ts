'use client';

import { useSyncExternalStore } from 'react';

const subscribers = new Set<() => void>();
let timerId: ReturnType<typeof setInterval> | null = null;
let currentSeconds = 0;

function subscribeToClock(callback: () => void) {
  subscribers.add(callback);
  if (!timerId && typeof window !== 'undefined') {
    currentSeconds = Math.floor(Date.now() / 1000);
    timerId = setInterval(() => {
      currentSeconds = Math.floor(Date.now() / 1000);
      subscribers.forEach((cb) => cb());
    }, 1000);
  }
  return () => {
    subscribers.delete(callback);
    if (subscribers.size === 0 && timerId) {
      clearInterval(timerId);
      timerId = null;
    }
  };
}

function getClockSnapshot(): number {
  if (typeof window === 'undefined') return 0;
  return currentSeconds || Math.floor(Date.now() / 1000);
}

function getServerClockSnapshot(): number {
  return 0;
}

/**
 * Global shared realtime clock hook (ticking every 1 second).
 * Fully compatible with React 19 & Next.js 16 SSR/SSG prerendering without hydration errors.
 */
export function useRealtimeClock(): number {
  return useSyncExternalStore(subscribeToClock, getClockSnapshot, getServerClockSnapshot);
}
