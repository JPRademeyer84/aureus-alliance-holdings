/**
 * Safe number formatting utilities
 * Prevents toLocaleString errors on undefined/null values
 */

/**
 * Safely format a number with locale string
 * @param value - The number to format (can be undefined/null)
 * @param fallback - Fallback value if input is invalid (default: 0)
 * @returns Formatted string
 */
export const safeToLocaleString = (value: number | undefined | null, fallback: number = 0): string => {
  const safeValue = value ?? fallback;
  return safeValue.toLocaleString();
};

/**
 * Safely format currency
 * @param value - The number to format as currency
 * @param currency - Currency code (default: 'USD')
 * @param fallback - Fallback value if input is invalid (default: 0)
 * @returns Formatted currency string
 */
export const safeCurrencyFormat = (
  value: number | undefined | null, 
  currency: string = 'USD', 
  fallback: number = 0
): string => {
  const safeValue = value ?? fallback;
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency,
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(safeValue);
};

/**
 * Safely format percentage
 * @param value - The number to format as percentage (0.2 = 20%)
 * @param fallback - Fallback value if input is invalid (default: 0)
 * @returns Formatted percentage string
 */
export const safePercentageFormat = (value: number | undefined | null, fallback: number = 0): string => {
  const safeValue = value ?? fallback;
  return new Intl.NumberFormat('en-US', {
    style: 'percent',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(safeValue);
};

/**
 * Safely format date
 * @param value - The date to format (string, Date, or timestamp)
 * @param fallback - Fallback string if input is invalid
 * @returns Formatted date string
 */
export const safeDateFormat = (
  value: string | Date | number | undefined | null, 
  fallback: string = 'N/A'
): string => {
  try {
    if (!value) return fallback;
    const date = new Date(value);
    if (isNaN(date.getTime())) return fallback;
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    });
  } catch {
    return fallback;
  }
};

/**
 * Safely format time
 * @param value - The date/time to format
 * @param fallback - Fallback string if input is invalid
 * @returns Formatted time string
 */
export const safeTimeFormat = (
  value: string | Date | number | undefined | null, 
  fallback: string = 'N/A'
): string => {
  try {
    if (!value) return fallback;
    const date = new Date(value);
    if (isNaN(date.getTime())) return fallback;
    return date.toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    return fallback;
  }
};

/**
 * Safely format a number with custom options
 * @param value - The number to format
 * @param options - Intl.NumberFormat options
 * @param fallback - Fallback value if input is invalid (default: 0)
 * @returns Formatted string
 */
export const safeNumberFormat = (
  value: number | undefined | null,
  options: Intl.NumberFormatOptions = {},
  fallback: number = 0
): string => {
  const safeValue = value ?? fallback;
  return new Intl.NumberFormat('en-US', options).format(safeValue);
};
