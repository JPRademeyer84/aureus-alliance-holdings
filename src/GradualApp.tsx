import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { UserProvider } from '@/contexts/UserContext';
import { AdminProvider } from '@/contexts/AdminContext';
import { Toaster } from '@/components/ui/sonner';

// Import your real components (with fixed Lucide icons)
import Navbar from '@/components/Navbar';
import Hero from '@/components/Hero';
import HowItWorks from '@/components/HowItWorks';
import Benefits from '@/components/Benefits';
import RewardsCalculator from '@/components/RewardsCalculator';
import AboutProject from '@/components/AboutProject';
import CallToAction from '@/components/CallToAction';
import Footer from '@/components/Footer';
// ConditionalLiveChat component doesn't exist, removing for now

// Real homepage using your actual components (with fixed Lucide icons)
const RealHomepage: React.FC = () => {
  return (
    <div className="min-h-screen bg-black text-white">
      <Navbar />
      <Hero />
      <HowItWorks />
      <Benefits />
      <RewardsCalculator />
      <AboutProject />
      <CallToAction />
      <Footer />
      {/* ConditionalLiveChat removed - component doesn't exist */}
    </div>
  );
};

// Simple auth page
const SimpleAuth: React.FC = () => {
  return (
    <div style={{
      minHeight: '100vh',
      backgroundColor: '#000',
      color: '#fff',
      display: 'flex',
      justifyContent: 'center',
      alignItems: 'center',
      padding: '20px'
    }}>
      <div style={{
        backgroundColor: '#1a1a1a',
        padding: '40px',
        borderRadius: '10px',
        border: '1px solid #333',
        maxWidth: '400px',
        width: '100%',
        textAlign: 'center'
      }}>
        <h2 style={{ color: '#ffd700', marginBottom: '30px' }}>Login / Register</h2>
        <p style={{ color: '#ccc', marginBottom: '30px' }}>
          Authentication page is working!
        </p>
        <button 
          onClick={() => window.location.href = '/'}
          style={{
            padding: '10px 20px',
            backgroundColor: '#ffd700',
            color: '#000',
            border: 'none',
            borderRadius: '5px',
            cursor: 'pointer'
          }}
        >
          Back to Home
        </button>
      </div>
    </div>
  );
};

// Simple investment page
const SimpleInvestment: React.FC = () => {
  return (
    <div style={{
      minHeight: '100vh',
      backgroundColor: '#000',
      color: '#fff',
      display: 'flex',
      justifyContent: 'center',
      alignItems: 'center',
      padding: '20px'
    }}>
      <div style={{
        backgroundColor: '#1a1a1a',
        padding: '40px',
        borderRadius: '10px',
        border: '1px solid #333',
        maxWidth: '400px',
        width: '100%',
        textAlign: 'center'
      }}>
        <h2 style={{ color: '#ffd700', marginBottom: '30px' }}>Investment Portal</h2>
        <p style={{ color: '#ccc', marginBottom: '30px' }}>
          Investment page is working!
        </p>
        <button 
          onClick={() => window.location.href = '/'}
          style={{
            padding: '10px 20px',
            backgroundColor: '#ffd700',
            color: '#000',
            border: 'none',
            borderRadius: '5px',
            cursor: 'pointer'
          }}
        >
          Back to Home
        </button>
      </div>
    </div>
  );
};

const GradualApp: React.FC = () => {
  return (
    <UserProvider>
      <AdminProvider>
        <Router>
          <Routes>
            <Route path="/" element={<RealHomepage />} />
            <Route path="/auth" element={<SimpleAuth />} />
            <Route path="/investment" element={<SimpleInvestment />} />
            <Route path="*" element={<RealHomepage />} />
          </Routes>
          <Toaster />
        </Router>
      </AdminProvider>
    </UserProvider>
  );
};

export default GradualApp;
