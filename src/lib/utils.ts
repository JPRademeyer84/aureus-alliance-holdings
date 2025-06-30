import { clsx, type ClassValue } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]) {
  // More defensive filtering and validation
  const safeInputs = inputs
    .filter(input => {
      // Filter out null, undefined, and invalid values
      if (input === null || input === undefined) return false;

      // Allow strings (including empty strings)
      if (typeof input === 'string') return true;

      // Allow objects (for conditional classes)
      if (typeof input === 'object' && input !== null) return true;

      // Allow arrays
      if (Array.isArray(input)) return true;

      // Filter out everything else
      return false;
    })
    .map(input => {
      // Ensure strings don't have prototype pollution issues
      if (typeof input === 'string') {
        return input.toString();
      }
      return input;
    });

  try {
    const result = twMerge(clsx(safeInputs));
    // Ensure result is always a string
    return typeof result === 'string' ? result : '';
  } catch (error) {
    console.warn('cn utility error:', error, 'inputs:', inputs);
    // Fallback to joining string inputs only
    const stringInputs = inputs.filter(input => typeof input === 'string');
    return stringInputs.join(' ').trim();
  }
}
