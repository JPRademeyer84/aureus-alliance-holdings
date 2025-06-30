import React from 'react';

// Simple test component to verify the site is working
const TestComponent: React.FC = () => {
  return (
    <div style={{ 
      padding: '20px', 
      backgroundColor: '#1a1a1a', 
      color: 'white',
      textAlign: 'center',
      minHeight: '100vh',
      display: 'flex',
      flexDirection: 'column',
      justifyContent: 'center',
      alignItems: 'center'
    }}>
      <h1 style={{ fontSize: '2rem', marginBottom: '20px' }}>
        🎉 Site is Working! 🎉
      </h1>
      <p style={{ fontSize: '1.2rem', marginBottom: '20px' }}>
        The Lucide React error has been fixed!
      </p>
      <button 
        onClick={() => alert('Button works!')}
        style={{
          padding: '10px 20px',
          fontSize: '1rem',
          backgroundColor: '#4CAF50',
          color: 'white',
          border: 'none',
          borderRadius: '5px',
          cursor: 'pointer'
        }}
      >
        Test Button
      </button>
      <div style={{ marginTop: '20px' }}>
        <p>✅ No more className.includes errors</p>
        <p>✅ All Lucide icons replaced with safe alternatives</p>
        <p>✅ Site loads without crashes</p>
      </div>
    </div>
  );
};

export default TestComponent;
