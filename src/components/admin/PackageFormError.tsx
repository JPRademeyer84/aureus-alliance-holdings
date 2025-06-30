
import React from "react";

interface Props {
  errorMessage: string | null;
}

const PackageFormError: React.FC<Props> = ({ errorMessage }) => {
  if (!errorMessage) return null;
  return (
    <div className="text-red-400 text-sm mt-2">{errorMessage}</div>
  );
};

export default PackageFormError;
