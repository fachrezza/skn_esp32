'use client';

import React, { useEffect, useState, useRef } from 'react';

interface AnimatedCounterProps {
  value: number;
  duration?: number;
  formatter?: (val: number) => string;
  className?: string;
}

export function AnimatedCounter({
  value,
  duration = 600,
  formatter = (val) => val.toLocaleString('id-ID'),
  className = '',
}: AnimatedCounterProps) {
  const [displayValue, setDisplayValue] = useState(value);
  const prevValueRef = useRef(value);

  useEffect(() => {
    let startTime: number | null = null;
    const startValue = prevValueRef.current;
    const diff = value - startValue;

    if (diff === 0) {
      setDisplayValue(value);
      return;
    }

    let animationFrameId: number;

    const step = (currentTime: number) => {
      if (!startTime) startTime = currentTime;
      const progress = Math.min((currentTime - startTime) / duration, 1);
      
      // Easing out cubic
      const ease = 1 - Math.pow(1 - progress, 3);
      const current = Math.round(startValue + diff * ease);

      setDisplayValue(current);

      if (progress < 1) {
        animationFrameId = requestAnimationFrame(step);
      } else {
        prevValueRef.current = value;
      }
    };

    animationFrameId = requestAnimationFrame(step);

    return () => {
      cancelAnimationFrame(animationFrameId);
      prevValueRef.current = value;
    };
  }, [value, duration]);

  return (
    <span className={`font-mono tabular-nums tracking-tight ${className}`}>
      {formatter(displayValue)}
    </span>
  );
}
