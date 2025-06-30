import * as React from "react"
import { cn } from "@/lib/utils"

// Safe icon components to avoid Lucide React issues
const EyeIcon = ({ className }: { className?: string }) => (
  <span className={cn("inline-block", className)} style={{ fontSize: '16px' }}>👁️</span>
);

const EyeOffIcon = ({ className }: { className?: string }) => (
  <span className={cn("inline-block", className)} style={{ fontSize: '16px' }}>🙈</span>
);

const Input = React.forwardRef<HTMLInputElement, React.ComponentProps<"input">>(
  ({ className, type, ...props }, ref) => {
    return (
      <input
        type={type}
        className={cn(
          "flex h-10 w-full rounded-md border bg-gray-800 border-gray-600 text-white px-3 py-2 text-base ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-white placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:border-blue-500 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm",
          className
        )}
        ref={ref}
        {...props}
      />
    )
  }
)
Input.displayName = "Input"

interface PasswordInputProps extends Omit<React.ComponentProps<"input">, "type"> {
  className?: string;
  theme?: 'dark' | 'light';
}

const PasswordInput = React.forwardRef<HTMLInputElement, PasswordInputProps>(
  ({ className, theme = 'dark', ...props }, ref) => {
    const [showPassword, setShowPassword] = React.useState(false)

    const darkTheme = "flex h-10 w-full rounded-md border bg-gray-800 border-gray-600 text-white px-3 py-2 pr-10 text-base ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-white placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:border-blue-500 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm";
    const lightTheme = "flex h-10 w-full rounded-md border bg-white border-gray-300 text-gray-900 px-3 py-2 pr-10 text-base ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-gray-900 placeholder:text-gray-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:border-blue-500 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm";

    const buttonTheme = theme === 'dark'
      ? "absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-white transition-colors"
      : "absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 transition-colors";

    return (
      <div className="relative">
        <input
          type={showPassword ? "text" : "password"}
          className={cn(
            theme === 'dark' ? darkTheme : lightTheme,
            className
          )}
          ref={ref}
          {...props}
        />
        <button
          type="button"
          className={buttonTheme}
          onClick={() => setShowPassword(!showPassword)}
        >
          {showPassword ? (
            <EyeOffIcon className="h-4 w-4" />
          ) : (
            <EyeIcon className="h-4 w-4" />
          )}
        </button>
      </div>
    )
  }
)
PasswordInput.displayName = "PasswordInput"

export { Input, PasswordInput }
