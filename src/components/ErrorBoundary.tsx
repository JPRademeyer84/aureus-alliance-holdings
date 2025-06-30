import React, { Component, ErrorInfo, ReactNode } from 'react';

interface Props {
  children: ReactNode;
  fallback?: ReactNode;
}

interface State {
  hasError: boolean;
  error?: Error;
  errorInfo?: ErrorInfo;
}

class ErrorBoundary extends Component<Props, State> {
  constructor(props: Props) {
    super(props);
    this.state = { hasError: false };
  }

  static getDerivedStateFromError(error: Error): State {
    // Only ignore the specific malformed SVG path error
    const errorMessage = error.message?.toLowerCase() || '';

    if (errorMessage.includes('tc0.2,0,0.4-0.2,0')) {
      // Don't show error boundary for this specific SVG error
      console.warn('Ignoring malformed SVG path error in React:', error.message);
      return { hasError: false };
    }

    // Update state so the next render will show the fallback UI for real errors
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, errorInfo: ErrorInfo) {
    // Only ignore the specific malformed SVG path error
    const errorMessage = error.message?.toLowerCase() || '';

    if (errorMessage.includes('tc0.2,0,0.4-0.2,0')) {
      // Don't crash React for this specific SVG error
      console.warn('Ignoring malformed SVG path error in React componentDidCatch:', error.message);
      return;
    }

    // Log the error to console and potentially to a logging service for real errors
    console.error('Error Boundary caught an error:', error, errorInfo);

    this.setState({
      error,
      errorInfo
    });

    // You can also log the error to an error reporting service here
    // logErrorToService(error, errorInfo);
  }

  handleReload = () => {
    window.location.reload();
  };

  handleGoHome = () => {
    window.location.href = '/';
  };

  render() {
    if (this.state.hasError) {
      // Custom fallback UI
      if (this.props.fallback) {
        return this.props.fallback;
      }

      // Default error UI - Safe version without external dependencies
      return (
        <div style={{
          minHeight: '100vh',
          backgroundColor: '#0E0E14',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          padding: '1rem',
          fontFamily: 'system-ui, -apple-system, sans-serif'
        }}>
          <div style={{
            width: '100%',
            maxWidth: '32rem',
            backgroundColor: '#23243a',
            border: '1px solid rgba(239, 68, 68, 0.3)',
            borderRadius: '0.5rem',
            padding: '2rem'
          }}>
            <div style={{ textAlign: 'center', marginBottom: '1.5rem' }}>
              <div style={{
                width: '4rem',
                height: '4rem',
                backgroundColor: 'rgba(239, 68, 68, 0.2)',
                borderRadius: '50%',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                margin: '0 auto 1rem',
                fontSize: '2rem'
              }}>
                ⚠️
              </div>
              <h2 style={{
                fontSize: '1.5rem',
                fontWeight: 'bold',
                color: 'white',
                margin: '0 0 1rem'
              }}>
                Oops! Something went wrong
              </h2>
            </div>

            <p style={{
              color: 'rgba(255, 255, 255, 0.7)',
              textAlign: 'center',
              marginBottom: '1.5rem'
            }}>
              We encountered an unexpected error. This has been logged and we'll look into it.
            </p>

            {process.env.NODE_ENV === 'development' && this.state.error && (
              <div style={{
                backgroundColor: 'rgba(0, 0, 0, 0.5)',
                padding: '1rem',
                borderRadius: '0.5rem',
                border: '1px solid rgba(239, 68, 68, 0.3)',
                marginBottom: '1.5rem'
              }}>
                <h4 style={{
                  color: '#f87171',
                  fontWeight: '600',
                  marginBottom: '0.5rem'
                }}>
                  Error Details (Development Mode):
                </h4>
                <pre style={{
                  fontSize: '0.75rem',
                  color: '#fca5a5',
                  overflow: 'auto',
                  maxHeight: '8rem',
                  margin: 0,
                  whiteSpace: 'pre-wrap'
                }}>
                  {this.state.error.toString()}
                </pre>
                {this.state.errorInfo && (
                  <details style={{ marginTop: '0.5rem' }}>
                    <summary style={{
                      color: '#f87171',
                      cursor: 'pointer',
                      fontSize: '0.875rem'
                    }}>
                      Component Stack
                    </summary>
                    <pre style={{
                      fontSize: '0.75rem',
                      color: '#fca5a5',
                      marginTop: '0.5rem',
                      overflow: 'auto',
                      maxHeight: '8rem',
                      margin: '0.5rem 0 0',
                      whiteSpace: 'pre-wrap'
                    }}>
                      {this.state.errorInfo.componentStack}
                    </pre>
                  </details>
                )}
              </div>
            )}

            <div style={{
              display: 'flex',
              flexDirection: 'column',
              gap: '0.75rem',
              paddingTop: '1rem'
            }}>
              <button
                onClick={this.handleReload}
                style={{
                  flex: 1,
                  background: 'linear-gradient(135deg, #D4AF37 0%, #FFD700 100%)',
                  color: 'black',
                  fontWeight: '600',
                  padding: '0.75rem 1rem',
                  borderRadius: '0.375rem',
                  border: 'none',
                  cursor: 'pointer',
                  fontSize: '0.875rem'
                }}
              >
                🔄 Reload Page
              </button>
              <button
                onClick={this.handleGoHome}
                style={{
                  flex: 1,
                  backgroundColor: 'transparent',
                  border: '1px solid rgba(212, 175, 55, 0.3)',
                  color: '#D4AF37',
                  padding: '0.75rem 1rem',
                  borderRadius: '0.375rem',
                  cursor: 'pointer',
                  fontSize: '0.875rem'
                }}
              >
                🏠 Go Home
              </button>
            </div>
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}

export default ErrorBoundary;
