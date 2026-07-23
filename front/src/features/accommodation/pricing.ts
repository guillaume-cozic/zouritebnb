/**
 * Client-side replica of the backend StayPriceCalculator: prices a stay night by
 * night (price-period override → weekend surcharge) then applies the best single
 * stay-level discount (weekly promotion vs last-minute, never stacked); services
 * billed with the reservation are added once per stay, never discounted. Kept in sync
 * with API/src/Shared/Domain/Service/StayPriceCalculator.php. The displayed estimate
 * is informational; the amount actually charged is always computed server-side.
 */
export interface PricePeriodInput {
  startDate: string;
  endDate: string;
  pricePerNight: number;
}

export interface ExtraServiceInput {
  price: number;
  billedWithReservation: boolean;
}

export interface StayPriceInput {
  pricePerNight: number;
  weeklyPromotionPercentage?: number | null;
  weekendSurchargePercentage?: number | null;
  lastMinuteDiscountPercentage?: number | null;
  lastMinuteDays?: number | null;
  pricePeriods?: PricePeriodInput[];
  extraServices?: ExtraServiceInput[];
}

export interface StayPriceResult {
  nights: number;
  subtotal: number;
  appliedDiscountPercentage: number | null;
  /** Flat per-stay total of the services billed with the reservation (not discounted). */
  extraServicesTotal: number;
}

const WEEKLY_PROMOTION_MIN_NIGHTS = 7;
const MS_PER_DAY = 86_400_000;

const startOfDay = (d: Date): Date => new Date(d.getFullYear(), d.getMonth(), d.getDate());

const toDayString = (d: Date): string => {
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
};

export function computeStayPrice(
  pricing: StayPriceInput,
  checkIn: Date,
  checkOut: Date,
  now: Date
): StayPriceResult {
  const start = startOfDay(checkIn);
  const end = startOfDay(checkOut);
  const nights = Math.max(0, Math.round((end.getTime() - start.getTime()) / MS_PER_DAY));
  const extraServicesTotal = (pricing.extraServices ?? [])
    .filter((s) => s.billedWithReservation)
    .reduce((sum, s) => sum + s.price, 0);

  if (nights === 0) {
    return { nights: 0, subtotal: 0, appliedDiscountPercentage: null, extraServicesTotal: 0 };
  }

  const periods = pricing.pricePeriods ?? [];
  let subtotal = 0;
  for (let i = 0; i < nights; i++) {
    const night = new Date(start.getTime() + i * MS_PER_DAY);
    const date = toDayString(night);
    let nightly = pricing.pricePerNight;
    for (const period of periods) {
      if (period.startDate <= date && date <= period.endDate) {
        nightly = period.pricePerNight;
        break;
      }
    }
    const dayOfWeek = night.getDay(); // 0=Sun … 5=Fri, 6=Sat
    if ((dayOfWeek === 5 || dayOfWeek === 6) && pricing.weekendSurchargePercentage != null) {
      nightly *= 1 + pricing.weekendSurchargePercentage / 100;
    }
    subtotal += nightly;
  }

  const candidates: number[] = [];
  if (
    nights >= WEEKLY_PROMOTION_MIN_NIGHTS &&
    pricing.weeklyPromotionPercentage != null &&
    pricing.weeklyPromotionPercentage > 0
  ) {
    candidates.push(pricing.weeklyPromotionPercentage);
  }
  if (pricing.lastMinuteDiscountPercentage != null && pricing.lastMinuteDays != null) {
    const nowDay = startOfDay(now);
    if (nowDay.getTime() <= start.getTime()) {
      const daysUntilCheckIn = Math.round((start.getTime() - nowDay.getTime()) / MS_PER_DAY);
      if (daysUntilCheckIn < pricing.lastMinuteDays) {
        candidates.push(pricing.lastMinuteDiscountPercentage);
      }
    }
  }

  const appliedDiscountPercentage = candidates.length > 0 ? Math.max(...candidates) : null;
  const discounted =
    appliedDiscountPercentage != null ? subtotal * (1 - appliedDiscountPercentage / 100) : subtotal;

  return {
    nights,
    subtotal: Math.round(discounted * 100) / 100,
    appliedDiscountPercentage,
    extraServicesTotal: Math.round(extraServicesTotal * 100) / 100,
  };
}
