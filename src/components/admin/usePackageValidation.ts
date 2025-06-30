
interface PackageInput {
  name?: string;
  price?: number;
  shares?: number;
  roi?: number;
  annual_dividends?: number;
  quarter_dividends?: number;
}

export function usePackageValidation() {
  function validate(pkg: PackageInput): string | null {
    if (!pkg.name) return "Package name is required.";
    if (pkg.price === undefined || isNaN(pkg.price)) return "Investment cost is required and must be a number.";
    if (pkg.shares === undefined || isNaN(pkg.shares)) return "Number of shares is required and must be a number.";
    if (pkg.roi === undefined || isNaN(pkg.roi)) return "ROI amount is required and must be a number.";
    if (pkg.annual_dividends === undefined || isNaN(pkg.annual_dividends)) return "Annual dividend is required and must be a number.";
    if (pkg.quarter_dividends === undefined || isNaN(pkg.quarter_dividends)) return "Quarterly dividend is required and must be a number.";
    return null;
  }
  return { validate };
}
