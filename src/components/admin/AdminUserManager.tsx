import React, { useState, useEffect } from 'react';
import { useToast } from '@/hooks/use-toast';
import { useAdmin } from '@/contexts/AdminContext';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input, PasswordInput } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Switch } from '@/components/ui/switch';
import { Users, Plus, Edit, Trash2, Shield, MessageCircle, Loader2 } from 'lucide-react';
import ApiConfig from '@/config/api';

interface AdminUser {
  id: string;
  username: string;
  email: string;
  full_name: string;
  role: 'super_admin' | 'admin' | 'chat_support';
  is_active: boolean;
  chat_status: 'online' | 'offline' | 'busy';
  last_activity: string;
  created_at: string;
}

interface AdminUserManagerProps {}

const AdminUserManager: React.FC<AdminUserManagerProps> = () => {
  const [admins, setAdmins] = useState<AdminUser[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
  const [isEditDialogOpen, setIsEditDialogOpen] = useState(false);
  const [selectedAdmin, setSelectedAdmin] = useState<AdminUser | null>(null);
  const [onlineCount, setOnlineCount] = useState(0);
  const [currentAdminRole, setCurrentAdminRole] = useState<string>('');
  
  // Form states
  const [formData, setFormData] = useState({
    username: '',
    password: '',
    email: '',
    full_name: '',
    role: 'chat_support' as const
  });

  const { admin } = useAdmin();
  const { toast } = useToast();

  useEffect(() => {
    fetchAdmins();
  }, []);

  const fetchAdmins = async () => {
    if (!admin?.id) return;
    
    setIsLoading(true);
    try {
      const response = await fetch(`${ApiConfig.endpoints.admin.manageAdmins}?current_admin_id=${admin.id}`);
      const data = await response.json();
      
      if (data.success) {
        setAdmins(data.data.admins);
        setOnlineCount(data.data.online_count);
        setCurrentAdminRole(data.data.current_admin_role);
      } else {
        throw new Error(data.message);
      }
    } catch (error) {
      console.error('Fetch admins error:', error);
      toast({
        title: 'Error',
        description: 'Failed to load admin users',
        variant: 'destructive',
      });
    } finally {
      setIsLoading(false);
    }
  };

  const handleCreateAdmin = async () => {
    if (!admin?.id) return;
    
    try {
      const response = await fetch(ApiConfig.endpoints.admin.manageAdmins, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'create',
          current_admin_id: admin.id,
          ...formData
        }),
      });

      const data = await response.json();
      if (data.success) {
        toast({
          title: 'Success',
          description: 'Admin user created successfully',
        });
        setIsCreateDialogOpen(false);
        setFormData({
          username: '',
          password: '',
          email: '',
          full_name: '',
          role: 'chat_support'
        });
        fetchAdmins();
      } else {
        throw new Error(data.message);
      }
    } catch (error) {
      console.error('Create admin error:', error);
      toast({
        title: 'Error',
        description: error instanceof Error ? error.message : 'Failed to create admin user',
        variant: 'destructive',
      });
    }
  };

  const handleUpdateAdmin = async (adminId: string, updates: Partial<AdminUser>) => {
    if (!admin?.id) return;

    try {
      const response = await fetch(ApiConfig.endpoints.admin.manageAdmins, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'update',
          current_admin_id: admin.id,
          admin_id: adminId,
          updates
        }),
      });

      const data = await response.json();
      if (data.success) {
        toast({
          title: 'Success',
          description: 'Admin user updated successfully',
        });
        fetchAdmins();
      } else {
        throw new Error(data.message);
      }
    } catch (error) {
      console.error('Update admin error:', error);
      toast({
        title: 'Error',
        description: error instanceof Error ? error.message : 'Failed to update admin user',
        variant: 'destructive',
      });
    }
  };

  const handleChatStatusToggle = async (adminId: string, currentStatus: 'online' | 'offline' | 'busy') => {
    if (!admin?.id) return;

    // Cycle through statuses: offline -> online -> busy -> offline
    const nextStatus = currentStatus === 'offline' ? 'online' :
                      currentStatus === 'online' ? 'busy' : 'offline';

    try {
      const response = await fetch(ApiConfig.endpoints.admin.manageAdmins, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'update_chat_status',
          current_admin_id: admin.id,
          admin_id: adminId,
          chat_status: nextStatus,
        }),
      });

      const data = await response.json();
      if (data.success) {
        toast({
          title: 'Chat Status Updated',
          description: `Status changed to ${nextStatus}`,
        });
        fetchAdmins();
      } else {
        throw new Error(data.message || data.error);
      }
    } catch (error) {
      console.error('Update chat status error:', error);
      toast({
        title: 'Error',
        description: error instanceof Error ? error.message : 'Failed to update chat status',
        variant: 'destructive',
      });
    }
  };

  const handleDeleteAdmin = async (adminId: string) => {
    if (!admin?.id) return;
    
    if (!confirm('Are you sure you want to deactivate this admin user?')) {
      return;
    }
    
    try {
      const response = await fetch(ApiConfig.endpoints.admin.manageAdmins, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'delete',
          current_admin_id: admin.id,
          admin_id: adminId
        }),
      });

      const data = await response.json();
      if (data.success) {
        toast({
          title: 'Success',
          description: 'Admin user deactivated successfully',
        });
        fetchAdmins();
      } else {
        throw new Error(data.message);
      }
    } catch (error) {
      console.error('Delete admin error:', error);
      toast({
        title: 'Error',
        description: error instanceof Error ? error.message : 'Failed to deactivate admin user',
        variant: 'destructive',
      });
    }
  };

  const updateChatStatus = async (status: 'online' | 'offline' | 'busy') => {
    if (!admin?.id) return;
    
    try {
      const response = await fetch(ApiConfig.endpoints.admin.manageAdmins, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'update_chat_status',
          current_admin_id: admin.id,
          chat_status: status
        }),
      });

      const data = await response.json();
      if (data.success) {
        toast({
          title: 'Success',
          description: `Chat status updated to ${status}`,
        });
        fetchAdmins();
      } else {
        throw new Error(data.message);
      }
    } catch (error) {
      console.error('Update chat status error:', error);
      toast({
        title: 'Error',
        description: 'Failed to update chat status',
        variant: 'destructive',
      });
    }
  };

  const getRoleBadge = (role: string) => {
    const roleConfig = {
      super_admin: { label: 'Super Admin', variant: 'destructive' as const },
      admin: { label: 'Admin', variant: 'default' as const },
      chat_support: { label: 'Chat Support', variant: 'secondary' as const }
    };
    
    const config = roleConfig[role as keyof typeof roleConfig] || roleConfig.chat_support;
    return <Badge variant={config.variant}>{config.label}</Badge>;
  };

  const getStatusBadge = (status: string) => {
    const statusConfig = {
      online: { label: 'Online', variant: 'default' as const, color: 'bg-green-500' },
      offline: { label: 'Offline', variant: 'secondary' as const, color: 'bg-gray-500' },
      busy: { label: 'Busy', variant: 'destructive' as const, color: 'bg-yellow-500' }
    };

    const config = statusConfig[status as keyof typeof statusConfig] || statusConfig.offline;
    return (
      <div className="flex items-center gap-2">
        <div className={`w-2 h-2 rounded-full ${config.color}`} />
        <Badge variant={config.variant}>{config.label}</Badge>
      </div>
    );
  };

  const getChatStatusToggle = (adminUser: AdminUser) => {
    const statusConfig = {
      online: {
        label: 'Online',
        color: 'bg-green-500 hover:bg-green-600',
        textColor: 'text-white',
        icon: '●'
      },
      offline: {
        label: 'Offline',
        color: 'bg-gray-500 hover:bg-gray-600',
        textColor: 'text-white',
        icon: '○'
      },
      busy: {
        label: 'Busy',
        color: 'bg-yellow-500 hover:bg-yellow-600',
        textColor: 'text-white',
        icon: '◐'
      }
    };

    const config = statusConfig[adminUser.chat_status as keyof typeof statusConfig] || statusConfig.offline;

    return (
      <Button
        variant="outline"
        size="sm"
        onClick={() => handleChatStatusToggle(adminUser.id, adminUser.chat_status)}
        className={`${config.color} ${config.textColor} border-0 min-w-[80px] transition-all duration-200`}
        title={`Click to change from ${config.label} (cycles: Offline → Online → Busy → Offline)`}
      >
        <span className="mr-1">{config.icon}</span>
        {config.label}
      </Button>
    );
  };

  const canCreateAdmin = currentAdminRole === 'super_admin' || currentAdminRole === 'admin';
  const canDeleteAdmin = currentAdminRole === 'super_admin';

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold flex items-center gap-2">
            <Users className="h-6 w-6" />
            Admin User Management
          </h2>
          <p className="text-muted-foreground mt-1">
            Manage admin users and their permissions
          </p>
        </div>
        
        <div className="flex items-center gap-4">
          <div className="text-sm text-muted-foreground">
            <MessageCircle className="h-4 w-4 inline mr-1" />
            {onlineCount} online
          </div>
          
          {canCreateAdmin && (
            <Dialog open={isCreateDialogOpen} onOpenChange={setIsCreateDialogOpen}>
              <DialogTrigger asChild>
                <Button>
                  <Plus className="h-4 w-4 mr-2" />
                  Add Admin
                </Button>
              </DialogTrigger>
              <DialogContent className="bg-gray-800 border-gray-700 text-white">
                <DialogHeader>
                  <DialogTitle className="text-white">Create New Admin User</DialogTitle>
                </DialogHeader>
                <div className="space-y-4">
                  <div>
                    <Label htmlFor="username" className="text-gray-200">Username</Label>
                    <Input
                      id="username"
                      value={formData.username}
                      onChange={(e) => setFormData(prev => ({ ...prev, username: e.target.value }))}
                      placeholder="Enter username"
                      className="bg-gray-700 border-gray-600 text-white placeholder:text-gray-400 focus:border-blue-500"
                    />
                  </div>
                  <div>
                    <Label htmlFor="password" className="text-gray-200">Password</Label>
                    <PasswordInput
                      id="password"
                      value={formData.password}
                      onChange={(e) => setFormData(prev => ({ ...prev, password: e.target.value }))}
                      placeholder="Enter password"
                      className="bg-gray-700 border-gray-600 text-white placeholder:text-gray-400 focus:border-blue-500"
                    />
                  </div>
                  <div>
                    <Label htmlFor="email" className="text-gray-200">Email</Label>
                    <Input
                      id="email"
                      type="email"
                      value={formData.email}
                      onChange={(e) => setFormData(prev => ({ ...prev, email: e.target.value }))}
                      placeholder="Enter email"
                      className="bg-gray-700 border-gray-600 text-white placeholder:text-gray-400 focus:border-blue-500"
                    />
                  </div>
                  <div>
                    <Label htmlFor="full_name" className="text-gray-200">Full Name</Label>
                    <Input
                      id="full_name"
                      value={formData.full_name}
                      onChange={(e) => setFormData(prev => ({ ...prev, full_name: e.target.value }))}
                      placeholder="Enter full name"
                      className="bg-gray-700 border-gray-600 text-white placeholder:text-gray-400 focus:border-blue-500"
                    />
                  </div>
                  <div>
                    <Label htmlFor="role" className="text-gray-200">Role</Label>
                    <Select value={formData.role} onValueChange={(value: any) => setFormData(prev => ({ ...prev, role: value }))}>
                      <SelectTrigger className="bg-gray-700 border-gray-600 text-white">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent className="bg-gray-700 border-gray-600">
                        <SelectItem value="chat_support" className="text-white hover:bg-gray-600">Chat Support</SelectItem>
                        {(currentAdminRole === 'super_admin' || currentAdminRole === 'admin') && (
                          <SelectItem value="admin" className="text-white hover:bg-gray-600">Admin</SelectItem>
                        )}
                        {currentAdminRole === 'super_admin' && (
                          <SelectItem value="super_admin" className="text-white hover:bg-gray-600">Super Admin</SelectItem>
                        )}
                      </SelectContent>
                    </Select>
                    <p className="text-xs text-gray-400 mt-1">
                      {formData.role === 'super_admin' && 'Full system access, can manage all admins'}
                      {formData.role === 'admin' && 'Can manage users and system settings, create chat support users'}
                      {formData.role === 'chat_support' && 'Can handle chat sessions and view own profile only'}
                    </p>
                  </div>
                  <div className="flex justify-end gap-2">
                    <Button
                      variant="outline"
                      onClick={() => setIsCreateDialogOpen(false)}
                      className="border-gray-600 text-gray-300 hover:bg-gray-700"
                    >
                      Cancel
                    </Button>
                    <Button
                      onClick={handleCreateAdmin}
                      className="bg-blue-600 hover:bg-blue-700 text-white"
                    >
                      Create Admin
                    </Button>
                  </div>
                </div>
              </DialogContent>
            </Dialog>
          )}
        </div>
      </div>

      {/* Chat Status Control */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <MessageCircle className="h-5 w-5" />
            Your Chat Status
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex items-center gap-4">
            <Button
              variant={admin && admins.find(a => a.id === admin.id)?.chat_status === 'online' ? 'default' : 'outline'}
              onClick={() => updateChatStatus('online')}
              className="bg-green-600 hover:bg-green-700"
            >
              Online
            </Button>
            <Button
              variant={admin && admins.find(a => a.id === admin.id)?.chat_status === 'busy' ? 'default' : 'outline'}
              onClick={() => updateChatStatus('busy')}
              className="bg-yellow-600 hover:bg-yellow-700"
            >
              Busy
            </Button>
            <Button
              variant={admin && admins.find(a => a.id === admin.id)?.chat_status === 'offline' ? 'default' : 'outline'}
              onClick={() => updateChatStatus('offline')}
            >
              Offline
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Admin Users Table */}
      <Card>
        <CardHeader>
          <CardTitle>Admin Users</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="flex items-center justify-center py-8">
              <Loader2 className="h-6 w-6 animate-spin" />
              <span className="ml-2">Loading admin users...</span>
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Username</TableHead>
                  <TableHead>Full Name</TableHead>
                  <TableHead>Email</TableHead>
                  <TableHead>Role</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Chat Status</TableHead>
                  <TableHead>Last Activity</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {admins.map((adminUser) => (
                  <TableRow key={adminUser.id}>
                    <TableCell className="font-medium">{adminUser.username}</TableCell>
                    <TableCell>{adminUser.full_name || '-'}</TableCell>
                    <TableCell>{adminUser.email || '-'}</TableCell>
                    <TableCell>{getRoleBadge(adminUser.role)}</TableCell>
                    <TableCell>
                      <Badge variant={adminUser.is_active ? 'default' : 'secondary'}>
                        {adminUser.is_active ? 'Active' : 'Inactive'}
                      </Badge>
                    </TableCell>
                    <TableCell>{getChatStatusToggle(adminUser)}</TableCell>
                    <TableCell>
                      {adminUser.last_activity 
                        ? new Date(adminUser.last_activity).toLocaleString()
                        : 'Never'
                      }
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        {adminUser.id !== admin?.id && canDeleteAdmin && (
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handleDeleteAdmin(adminUser.id)}
                            className="text-red-600 hover:text-red-700"
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        )}
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </div>
  );
};

export default AdminUserManager;
