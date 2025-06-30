import React, { useState, useEffect } from 'react';
import { 
  Plus, 
  Edit, 
  Trash2, 
  Gift, 
  DollarSign, 
  Calendar, 
  User, 
  CheckCircle, 
  XCircle,
  Copy,
  Download
} from 'lucide-react';

const NFTCouponsManager = () => {
  const [coupons, setCoupons] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [editingCoupon, setEditingCoupon] = useState(null);
  const [formData, setFormData] = useState({
    coupon_code: '',
    value: '',
    description: '',
    notes: '',
    max_uses: 1,
    expires_in_days: '',
    assigned_username: ''
  });

  useEffect(() => {
    fetchCoupons();
  }, []);

  const fetchCoupons = async () => {
    setLoading(true);
    try {
      const response = await fetch('http://localhost/aureus-angel-alliance/api/coupons/index.php?action=admin_coupons', {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include'
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();

      if (data.success) {
        setCoupons(data.data || []);
      } else {
        console.error('Failed to fetch coupons:', data.error);
        alert('Failed to fetch coupons: ' + (data.error || 'Unknown error'));
      }
    } catch (error) {
      console.error('Error fetching coupons:', error);
      alert('Error fetching coupons: ' + error.message);
    } finally {
      setLoading(false);
    }
  };

  const handleCreateCoupon = async (e) => {
    e.preventDefault();

    try {
      const response = await fetch('/api/coupons/index.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          action: 'create_coupon',
          ...formData
        })
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();

      if (data.success) {
        setShowCreateModal(false);
        setFormData({
          coupon_code: '',
          value: '',
          description: '',
          notes: '',
          max_uses: 1,
          expires_in_days: '',
          assigned_username: ''
        });
        fetchCoupons();
        alert('Coupon created successfully!');
      } else {
        alert('Error creating coupon: ' + (data.error || 'Unknown error'));
      }
    } catch (error) {
      console.error('Error creating coupon:', error);
      alert('Error creating coupon: ' + error.message);
    }
  };

  const handleUpdateCoupon = async (couponId, updates) => {
    try {
      const response = await fetch('/api/coupons/index.php', {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          id: couponId,
          ...updates
        })
      });
      
      const data = await response.json();
      
      if (data.success) {
        fetchCoupons();
        alert('Coupon updated successfully!');
      } else {
        alert('Error updating coupon: ' + data.error);
      }
    } catch (error) {
      console.error('Error updating coupon:', error);
      alert('Error updating coupon');
    }
  };

  const handleDeleteCoupon = async (couponId) => {
    if (!confirm('Are you sure you want to delete this coupon?')) {
      return;
    }
    
    try {
      const response = await fetch('/api/coupons/index.php', {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          id: couponId
        })
      });
      
      const data = await response.json();
      
      if (data.success) {
        fetchCoupons();
        alert('Coupon deleted successfully!');
      } else {
        alert('Error deleting coupon: ' + data.error);
      }
    } catch (error) {
      console.error('Error deleting coupon:', error);
      alert('Error deleting coupon');
    }
  };

  const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    alert('Copied to clipboard!');
  };

  const exportCoupons = () => {
    const csvContent = [
      ['Code', 'Value', 'Status', 'Uses', 'Expires', 'Created'].join(','),
      ...coupons.map(coupon => [
        coupon.coupon_code,
        `$${coupon.value}`,
        coupon.is_used ? 'Used' : (coupon.is_active ? 'Active' : 'Inactive'),
        `${coupon.current_uses}/${coupon.max_uses}`,
        coupon.expires_at ? new Date(coupon.expires_at).toLocaleDateString() : 'Never',
        new Date(coupon.created_at).toLocaleDateString()
      ].join(','))
    ].join('\n');
    
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'nft_coupons.csv';
    a.click();
    window.URL.revokeObjectURL(url);
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-white">NFT Coupons Manager</h1>
          <p className="text-gray-400">Create and manage promotional coupons for NFT credits</p>
        </div>
        <div className="flex space-x-3">
          <button
            onClick={exportCoupons}
            className="flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
          >
            <Download className="w-4 h-4 mr-2" />
            Export
          </button>
          <button
            onClick={() => setShowCreateModal(true)}
            className="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            <Plus className="w-4 h-4 mr-2" />
            Create Coupon
          </button>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div className="bg-gray-800 p-6 rounded-lg">
          <div className="flex items-center">
            <Gift className="w-8 h-8 text-blue-500 mr-3" />
            <div>
              <p className="text-sm text-gray-400">Total Coupons</p>
              <p className="text-2xl font-bold text-white">{coupons.length}</p>
            </div>
          </div>
        </div>
        
        <div className="bg-gray-800 p-6 rounded-lg">
          <div className="flex items-center">
            <CheckCircle className="w-8 h-8 text-green-500 mr-3" />
            <div>
              <p className="text-sm text-gray-400">Active Coupons</p>
              <p className="text-2xl font-bold text-white">
                {coupons.filter(c => c.is_active && !c.is_used).length}
              </p>
            </div>
          </div>
        </div>
        
        <div className="bg-gray-800 p-6 rounded-lg">
          <div className="flex items-center">
            <XCircle className="w-8 h-8 text-red-500 mr-3" />
            <div>
              <p className="text-sm text-gray-400">Used Coupons</p>
              <p className="text-2xl font-bold text-white">
                {coupons.filter(c => c.is_used).length}
              </p>
            </div>
          </div>
        </div>
        
        <div className="bg-gray-800 p-6 rounded-lg">
          <div className="flex items-center">
            <DollarSign className="w-8 h-8 text-yellow-500 mr-3" />
            <div>
              <p className="text-sm text-gray-400">Total Value</p>
              <p className="text-2xl font-bold text-white">
                ${coupons.reduce((sum, c) => sum + parseFloat(c.value), 0).toFixed(2)}
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Coupons Table */}
      <div className="bg-gray-800 rounded-lg overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-700">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                  Code
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                  Value
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                  Status
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                  Usage
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                  Expires
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                  Assigned To
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-700">
              {coupons.map((coupon) => (
                <tr key={coupon.id} className="hover:bg-gray-700">
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="flex items-center">
                      <span className="text-sm font-medium text-white">
                        {coupon.coupon_code}
                      </span>
                      <button
                        onClick={() => copyToClipboard(coupon.coupon_code)}
                        className="ml-2 text-gray-400 hover:text-white"
                      >
                        <Copy className="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className="text-sm text-green-400 font-medium">
                      ${coupon.value}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                      coupon.is_used 
                        ? 'bg-red-100 text-red-800' 
                        : coupon.is_active 
                          ? 'bg-green-100 text-green-800' 
                          : 'bg-gray-100 text-gray-800'
                    }`}>
                      {coupon.is_used ? 'Used' : (coupon.is_active ? 'Active' : 'Inactive')}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                    {coupon.current_uses}/{coupon.max_uses}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                    {coupon.expires_at 
                      ? new Date(coupon.expires_at).toLocaleDateString()
                      : 'Never'
                    }
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                    {coupon.assigned_to_username || 'Anyone'}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div className="flex space-x-2">
                      <button
                        onClick={() => handleUpdateCoupon(coupon.id, { 
                          is_active: !coupon.is_active 
                        })}
                        className={`p-1 rounded ${
                          coupon.is_active 
                            ? 'text-red-400 hover:text-red-300' 
                            : 'text-green-400 hover:text-green-300'
                        }`}
                        title={coupon.is_active ? 'Deactivate' : 'Activate'}
                      >
                        {coupon.is_active ? <XCircle className="w-4 h-4" /> : <CheckCircle className="w-4 h-4" />}
                      </button>
                      {!coupon.is_used && (
                        <button
                          onClick={() => handleDeleteCoupon(coupon.id)}
                          className="p-1 text-red-400 hover:text-red-300"
                          title="Delete"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Create Coupon Modal */}
      {showCreateModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-gray-800 p-6 rounded-lg w-full max-w-md">
            <h2 className="text-xl font-bold text-white mb-4">Create New Coupon</h2>
            
            <form onSubmit={handleCreateCoupon} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-300 mb-1">
                  Coupon Code (optional)
                </label>
                <input
                  type="text"
                  value={formData.coupon_code}
                  onChange={(e) => setFormData({...formData, coupon_code: e.target.value.toUpperCase()})}
                  className="w-full px-3 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Leave empty for auto-generation"
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-300 mb-1">
                  Value ($) *
                </label>
                <input
                  type="number"
                  step="0.01"
                  min="0.01"
                  value={formData.value}
                  onChange={(e) => setFormData({...formData, value: e.target.value})}
                  className="w-full px-3 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-300 mb-1">
                  Description
                </label>
                <input
                  type="text"
                  value={formData.description}
                  onChange={(e) => setFormData({...formData, description: e.target.value})}
                  className="w-full px-3 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Promotional coupon description"
                />
              </div>
              
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-1">
                    Max Uses
                  </label>
                  <input
                    type="number"
                    min="1"
                    value={formData.max_uses}
                    onChange={(e) => setFormData({...formData, max_uses: parseInt(e.target.value)})}
                    className="w-full px-3 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                
                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-1">
                    Expires in Days
                  </label>
                  <input
                    type="number"
                    min="1"
                    value={formData.expires_in_days}
                    onChange={(e) => setFormData({...formData, expires_in_days: e.target.value})}
                    className="w-full px-3 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Never"
                  />
                </div>
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-300 mb-1">
                  Assign to Username (optional)
                </label>
                <input
                  type="text"
                  value={formData.assigned_username}
                  onChange={(e) => setFormData({...formData, assigned_username: e.target.value})}
                  className="w-full px-3 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Leave empty for anyone to use"
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-300 mb-1">
                  Admin Notes
                </label>
                <textarea
                  value={formData.notes}
                  onChange={(e) => setFormData({...formData, notes: e.target.value})}
                  className="w-full px-3 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  rows="2"
                  placeholder="Internal notes about this coupon"
                />
              </div>
              
              <div className="flex justify-end space-x-3 pt-4">
                <button
                  type="button"
                  onClick={() => setShowCreateModal(false)}
                  className="px-4 py-2 text-gray-300 hover:text-white transition-colors"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                  Create Coupon
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default NFTCouponsManager;
