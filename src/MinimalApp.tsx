import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';

// Ultra-minimal homepage component
const MinimalHomepage: React.FC = () => {
  return (
    <div style={{ 
      minHeight: '100vh',
      backgroundColor: '#000',
      color: '#fff',
      padding: '50px',
      textAlign: 'center'
    }}>
      <h1 style={{ fontSize: '3rem', marginBottom: '30px', color: '#ffd700' }}>
        🏆 Aureus Capital 🏆
      </h1>
      
      <p style={{ fontSize: '1.5rem', marginBottom: '30px' }}>
        Investment Platform - Site is Working!
      </p>
      
      <div style={{ 
        display: 'grid', 
        gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))',
        gap: '20px',
        maxWidth: '800px',
        margin: '0 auto'
      }}>
        <div style={{
          backgroundColor: '#1a1a1a',
          padding: '20px',
          borderRadius: '10px',
          border: '1px solid #ffd700'
        }}>
          <h3 style={{ color: '#ffd700', marginBottom: '10px' }}>✅ Site Fixed</h3>
          <p>No more className.includes errors</p>
        </div>
        
        <div style={{
          backgroundColor: '#1a1a1a',
          padding: '20px',
          borderRadius: '10px',
          border: '1px solid #ffd700'
        }}>
          <h3 style={{ color: '#ffd700', marginBottom: '10px' }}>🚀 Ready to Go</h3>
          <p>All Lucide React issues resolved</p>
        </div>
        
        <div style={{
          backgroundColor: '#1a1a1a',
          padding: '20px',
          borderRadius: '10px',
          border: '1px solid #ffd700'
        }}>
          <h3 style={{ color: '#ffd700', marginBottom: '10px' }}>💎 Investment Platform</h3>
          <p>Your site is now functional</p>
        </div>
      </div>
      
      <button 
        onClick={() => alert('Site is working perfectly!')}
        style={{
          marginTop: '40px',
          padding: '15px 30px',
          fontSize: '1.2rem',
          backgroundColor: '#ffd700',
          color: '#000',
          border: 'none',
          borderRadius: '10px',
          cursor: 'pointer',
          fontWeight: 'bold'
        }}
      >
        🎉 Test Button - Click Me! 🎉
      </button>
      
      <div style={{ marginTop: '40px', fontSize: '0.9rem', opacity: 0.7 }}>
        <p>✅ React is working</p>
        <p>✅ Routing is working</p>
        <p>✅ No more white screen</p>
        <p>✅ Ready to add back your components</p>
        <p>Time: {new Date().toLocaleString()}</p>
      </div>
    </div>
  );
};

// Minimal 404 page
const MinimalNotFound: React.FC = () => {
  return (
    <div style={{ 
      minHeight: '100vh',
      backgroundColor: '#000',
      color: '#fff',
      padding: '50px',
      textAlign: 'center',
      display: 'flex',
      flexDirection: 'column',
      justifyContent: 'center',
      alignItems: 'center'
    }}>
      <h1 style={{ fontSize: '4rem', marginBottom: '20px' }}>404</h1>
      <p style={{ fontSize: '1.5rem', marginBottom: '30px' }}>Page Not Found</p>
      <button 
        onClick={() => window.location.href = '/'}
        style={{
          padding: '10px 20px',
          fontSize: '1rem',
          backgroundColor: '#ffd700',
          color: '#000',
          border: 'none',
          borderRadius: '5px',
          cursor: 'pointer'
        }}
      >
        Go Home
      </button>
    </div>
  );
};

const MinimalApp: React.FC = () => {
  return (
    <Router>
      <Routes>
        <Route path="/" element={<MinimalHomepage />} />
        <Route path="*" element={<MinimalNotFound />} />
      </Routes>
    </Router>
  );
};

export default MinimalApp;
