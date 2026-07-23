import { describe, expect, test } from 'vitest';
import { computeStayPrice } from './pricing';

const LONG_AGO = new Date('2026-01-01T00:00:00');

describe('computeStayPrice', () => {
  test('flat nightly price without any rule', () => {
    const r = computeStayPrice(
      { pricePerNight: 100 },
      new Date('2026-06-10T15:00:00'),
      new Date('2026-06-14T11:00:00'),
      LONG_AGO
    );
    expect(r.nights).toBe(4);
    expect(r.subtotal).toBe(400);
    expect(r.appliedDiscountPercentage).toBeNull();
  });

  test('services billed with the reservation are totalled apart, optional ones excluded', () => {
    const r = computeStayPrice(
      {
        pricePerNight: 100,
        extraServices: [
          { price: 30, billedWithReservation: true },
          { price: 12.5, billedWithReservation: false },
          { price: 20, billedWithReservation: true },
        ],
      },
      new Date('2026-06-10T15:00:00'),
      new Date('2026-06-14T11:00:00'),
      LONG_AGO
    );
    expect(r.subtotal).toBe(400);
    expect(r.extraServicesTotal).toBe(50);
  });

  test('billed services are not affected by stay discounts', () => {
    const r = computeStayPrice(
      {
        pricePerNight: 100,
        weeklyPromotionPercentage: 20,
        extraServices: [{ price: 30, billedWithReservation: true }],
      },
      new Date('2026-06-10T15:00:00'),
      new Date('2026-06-17T11:00:00'),
      LONG_AGO
    );
    expect(r.subtotal).toBe(560);
    expect(r.extraServicesTotal).toBe(30);
  });

  test('weekly promotion from 7 nights', () => {
    const r = computeStayPrice(
      { pricePerNight: 100, weeklyPromotionPercentage: 20 },
      new Date('2026-06-10T15:00:00'),
      new Date('2026-06-17T11:00:00'),
      LONG_AGO
    );
    expect(r.subtotal).toBe(560);
    expect(r.appliedDiscountPercentage).toBe(20);
  });

  test('weekend surcharge on Friday and Saturday nights', () => {
    // 2026-06-12 is a Friday; nights Fri 12 + Sat 13.
    const r = computeStayPrice(
      { pricePerNight: 100, weekendSurchargePercentage: 50 },
      new Date('2026-06-12T15:00:00'),
      new Date('2026-06-14T11:00:00'),
      LONG_AGO
    );
    expect(r.subtotal).toBe(300);
  });

  test('price-period override wins over base', () => {
    const r = computeStayPrice(
      {
        pricePerNight: 100,
        pricePeriods: [{ startDate: '2026-06-15', endDate: '2026-06-16', pricePerNight: 200 }],
      },
      new Date('2026-06-15T15:00:00'),
      new Date('2026-06-17T11:00:00'),
      LONG_AGO
    );
    expect(r.subtotal).toBe(400);
  });

  test('last-minute discount applies within the window', () => {
    const r = computeStayPrice(
      { pricePerNight: 100, lastMinuteDiscountPercentage: 10, lastMinuteDays: 7 },
      new Date('2026-06-15T15:00:00'),
      new Date('2026-06-17T11:00:00'),
      new Date('2026-06-13T09:00:00')
    );
    expect(r.subtotal).toBe(180);
    expect(r.appliedDiscountPercentage).toBe(10);
  });

  test('last-minute discount ignored outside the window', () => {
    const r = computeStayPrice(
      { pricePerNight: 100, lastMinuteDiscountPercentage: 10, lastMinuteDays: 7 },
      new Date('2026-06-15T15:00:00'),
      new Date('2026-06-17T11:00:00'),
      new Date('2026-06-01T09:00:00')
    );
    expect(r.subtotal).toBe(200);
    expect(r.appliedDiscountPercentage).toBeNull();
  });

  test('best of weekly and last-minute discounts wins', () => {
    const r = computeStayPrice(
      {
        pricePerNight: 100,
        weeklyPromotionPercentage: 10,
        lastMinuteDiscountPercentage: 30,
        lastMinuteDays: 7,
      },
      new Date('2026-06-15T15:00:00'),
      new Date('2026-06-22T11:00:00'),
      new Date('2026-06-10T09:00:00')
    );
    expect(r.appliedDiscountPercentage).toBe(30);
    expect(r.subtotal).toBe(490);
  });
});
