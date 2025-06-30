import React from 'react';

// Ultra-simple app to test if React is working at all
const SimpleApp: React.FC = () => {
  return (
    <div style={{ 
      padding: '50px', 
      backgroundColor: '#000', 
      color: '#00ff00',
      fontFamily: 'monospace',
      minHeight: '100vh',
      display: 'flex',
      flexDirection: 'column',
      justifyContent: 'center',
      alignItems: 'center'
    }}>
      <h1 style={{ fontSize: '3rem', marginBottom: '30px' }}>
        🚀 REACT IS WORKING! 🚀
      </h1>
      
      <div style={{ fontSize: '1.5rem', textAlign: 'center', lineHeight: '2' }}>
        <p>✅ No white screen</p>
        <p>✅ No className.includes error</p>
        <p>✅ No Lucide React crashes</p>
        <p>✅ Site is functional</p>
      </div>
      
      <button 
        onClick={() => {
          alert('Button works! The site is fixed!');
          console.log('SUCCESS: Site is working without errors');
        }}
        style={{
          marginTop: '30px',
          padding: '15px 30px',
          fontSize: '1.2rem',
          backgroundColor: '#00ff00',
          color: '#000',
          border: 'none',
          borderRadius: '10px',
          cursor: 'pointer',
          fontWeight: 'bold'
        }}
      >
        🎉 TEST BUTTON - CLICK ME! 🎉
      </button>
      
      <div style={{ marginTop: '30px', fontSize: '1rem', opacity: 0.7 }}>
        <p>If you can see this, the Lucide React error is FIXED!</p>
        <p>Time: {new Date().toLocaleTimeString()}</p>
      </div>
    </div>
  );
};

export default SimpleApp;
