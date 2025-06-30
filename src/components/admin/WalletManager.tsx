
import React from 'react';
import { useAdmin } from '@/contexts/AdminContext';
import WalletManagement from './WalletManagement';

const WalletManager: React.FC = () => {
  const { admin } = useAdmin();

  if (!admin) {
    return (
      <div className="text-center py-8">
        <p className="text-red-400">Admin authentication required</p>
      </div>
    );
  }

  return <WalletManagement adminId={admin.id} />;
};

export default WalletManager;
