import React from 'react';

const TestSection: React.FC = () => {
  return (
    <section className="py-16 px-6 bg-gray-900">
      <div className="max-w-4xl mx-auto text-center">
        <h2 className="text-3xl font-bold text-white mb-4">Test Section</h2>
        <p className="text-white/70">This is a test section to verify JSX compilation works.</p>
      </div>
    </section>
  );
};

export default TestSection;
