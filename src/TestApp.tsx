import React from 'react';

const TestApp: React.FC = () => {
  return (
    <div style={{ 
      minHeight: '100vh', 
      backgroundColor: '#0E0E14', 
      color: 'white', 
      padding: '2rem',
      fontFamily: 'system-ui, -apple-system, sans-serif'
    }}>
      <h1 style={{ fontSize: '2rem', marginBottom: '1rem' }}>
        🚀 Aureus Angel Alliance - Test Mode
      </h1>
      <p style={{ marginBottom: '1rem' }}>
        Testing basic functionality without complex components.
      </p>
      <div style={{ 
        backgroundColor: '#23243a', 
        padding: '1rem', 
        borderRadius: '0.5rem',
        marginBottom: '1rem'
      }}>
        <h2 style={{ fontSize: '1.25rem', marginBottom: '0.5rem' }}>
          ✅ Basic React App Working
        </h2>
        <p>If you can see this, React is loading properly.</p>
      </div>
      <button 
        onClick={() => {
          console.log('Testing button click');
          alert('Button works! Now testing full app...');
        }}
        style={{
          background: 'linear-gradient(135deg, #D4AF37 0%, #FFD700 100%)',
          color: 'black',
          fontWeight: '600',
          padding: '0.75rem 1.5rem',
          borderRadius: '0.375rem',
          border: 'none',
          cursor: 'pointer',
          fontSize: '1rem',
          marginRight: '1rem'
        }}
      >
        🧪 Test Button
      </button>
      <button 
        onClick={() => window.location.reload()}
        style={{
          backgroundColor: 'transparent',
          border: '1px solid #D4AF37',
          color: '#D4AF37',
          fontWeight: '600',
          padding: '0.75rem 1.5rem',
          borderRadius: '0.375rem',
          cursor: 'pointer',
          fontSize: '1rem'
        }}
      >
        🔄 Reload
      </button>
    </div>
  );
};

export default TestApp;
